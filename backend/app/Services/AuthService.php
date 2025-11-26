<?php

namespace App\Services;

use App\Models\User;
use App\Models\PasswordResetToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\TemporaryPasswordNotification;
use Illuminate\Http\UploadedFile;

class AuthService
{
    /**
     * Register a new user and send verification email.
     */
    public function register(array $data): User
    {
        // Defensive: trim and validate inputs
        $data['name'] = trim($data['name'] ?? '');
        $data['email'] = trim(strtolower($data['email'] ?? ''));
        
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            throw new \InvalidArgumentException('Name, email and password are required');
        }
        
        $verificationToken = Str::random(64);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'user',
            'verification_token' => $verificationToken,
            'failed_login_attempts' => 0,
        ]);
        
        try {
            $user->notify(new VerifyEmailNotification($verificationToken));
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            // Don't fail registration if email fails - user can resend
        }
        
        return $user;
    }

    /**
     * Attempt login; returns structured result array describing outcome.
     */
    public function attemptLogin(string $email, string $password): array
    {
        // Defensive: trim and normalize email
        $email = trim(strtolower($email));
        
        if (empty($email) || empty($password)) {
            return ['status' => 'no_user'];
        }
        
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return $this->handleFailedAttempt($user);
        }

        if (!$user->email_verified_at) {
            if (!$user->verification_token) {
                $verificationToken = Str::random(64);
                $user->verification_token = $verificationToken;
                $user->save();
                $user->notify(new VerifyEmailNotification($verificationToken));
            }
            return ['status' => 'unverified'];
        }

        $user->failed_login_attempts = 0;
        $user->save();
        $token = $user->createToken('auth_token', [], now()->addHours(2))->plainTextToken;
        return ['status' => 'ok', 'token' => $token, 'user' => $user];
    }

    private function handleFailedAttempt(?User $user): array
    {
        if (!$user) {
            return ['status' => 'no_user'];
        }

        $user->increment('failed_login_attempts');

        if ($user->failed_login_attempts == 5) {
            // Send temporary password
            $this->sendTemporaryPassword($user);
            return [
                'status' => 'fifth_attempt',
                'failed_attempts' => $user->failed_login_attempts,
                'account_verified' => (bool) $user->email_verified_at,
                'max_attempts' => 5,
            ];
        }

        if ($user->failed_login_attempts > 5) {
            return [
                'status' => 'too_many',
                'failed_attempts' => $user->failed_login_attempts,
                'account_verified' => (bool) $user->email_verified_at,
                'max_attempts' => 5,
            ];
        }

        $remainingAttempts = 5 - $user->failed_login_attempts;
        return [
            'status' => 'wrong_password',
            'failed_attempts' => $user->failed_login_attempts,
            'remaining_attempts' => $remainingAttempts,
            'account_verified' => (bool) $user->email_verified_at,
            'max_attempts' => 5,
        ];
    }

    private function sendTemporaryPassword(User $user): void
    {
        // Defensive: ensure user has ID
        if (!$user || !$user->id) {
            \Log::error('Cannot send temporary password: invalid user');
            return;
        }
        
        // Invalidate old temporary passwords
        PasswordResetToken::where('user_id', $user->id)
            ->where('type', 'temporary')
            ->where('used', false)
            ->update(['used' => true]);

        // Generate 12-character temporary password (alphanumeric, readable)
        $temporaryPassword = Str::upper(Str::random(4)) . '-' . Str::upper(Str::random(4)) . '-' . Str::upper(Str::random(4));
        
        // Store hashed version in database
        PasswordResetToken::create([
            'user_id' => $user->id,
            'token' => Hash::make($temporaryPassword),
            'type' => 'temporary',
            'expires_at' => now()->addMinutes(15),
            'ip_address' => request()->ip() ?? 'unknown',
        ]);

        // Send plain password via email (only time it's visible)
        try {
            $user->notify(new TemporaryPasswordNotification($temporaryPassword));
            \Log::info('Temporary password sent', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            \Log::error('Failed to send temporary password email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Verify email token.
     */
    public function verifyToken(string $token): array
    {
        $user = User::where('verification_token', $token)->first();

        if (!$user) {
            return ['status' => 'invalid'];
        }
        if ($user->email_verified_at) {
            return ['status' => 'already'];
        }

        $user->email_verified_at = now();
        $user->verification_token = null;
        $user->failed_login_attempts = 0;
        $user->save();

        return ['status' => 'verified', 'user' => $user];
    }

    /**
     * Update avatar image.
     */
    public function updateAvatar(User $user, UploadedFile $file): string
    {
        // Defensive: validate inputs
        if (!$user || !$user->id) {
            throw new \InvalidArgumentException('Invalid user');
        }
        
        if (!$file || !$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }
        
        // Delete old avatar if exists
        if ($user->avatar_path) {
            $oldPath = storage_path('app/public/' . $user->avatar_path);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
        
        try {
            $path = $file->store('avatars', 'public');
            $user->avatar_path = $path;
            $user->save();
            \Log::info('Avatar updated', ['user_id' => $user->id, 'path' => $path]);
            return $path;
        } catch (\Exception $e) {
            \Log::error('Avatar upload failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to save avatar: ' . $e->getMessage());
        }
    }
}
