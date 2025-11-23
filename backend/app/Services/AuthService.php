<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\UploadedFile;

class AuthService
{
    /**
     * Register a new user and send verification email.
     */
    public function register(array $data): User
    {
        $verificationToken = Str::random(64);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'user',
            'verification_token' => $verificationToken,
            'failed_login_attempts' => 0,
        ]);
        $user->notify(new VerifyEmailNotification($verificationToken));
        return $user;
    }

    /**
     * Attempt login; returns structured result array describing outcome.
     */
    public function attemptLogin(string $email, string $password): array
    {
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
            $verificationToken = Str::random(64);
            $user->verification_token = $verificationToken;
            $user->save();
            $user->notify(new VerifyEmailNotification($verificationToken));
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
        if ($user->avatar_path) {
            $oldPath = storage_path('app/public/' . $user->avatar_path);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
        $path = $file->store('avatars', 'public');
        $user->avatar_path = $path;
        $user->save();
        return $path;
    }
}
