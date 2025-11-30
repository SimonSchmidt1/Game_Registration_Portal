<?php

namespace App\Services;

use App\Models\User;
use App\Models\PasswordResetToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
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
            'student_type' => $data['student_type'] ?? null,
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
     * Attempt admin login with special credentials.
     * Includes rate limiting for security.
     */
    public function attemptAdminLogin(string $email, string $password): array
    {
        $adminEmail = config('admin.email');
        $adminPassword = config('admin.password');
        
        // Ensure admin credentials are configured
        if (empty($adminEmail) || empty($adminPassword)) {
            \Log::warning('Admin login attempted but credentials not configured');
            return ['status' => 'invalid_credentials'];
        }
        
        // Rate limiting: max 5 attempts per minute per IP
        $rateLimitKey = 'admin-login:' . request()->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            \Log::warning('Admin login rate limited', ['ip' => request()->ip(), 'seconds_remaining' => $seconds]);
            return ['status' => 'rate_limited', 'retry_after' => $seconds];
        }
        
        // Use timing-safe comparison for credentials
        $emailMatches = hash_equals(strtolower(trim($adminEmail)), strtolower(trim($email)));
        $passwordMatches = hash_equals($adminPassword, $password);
        
        if (!$emailMatches || !$passwordMatches) {
            RateLimiter::hit($rateLimitKey, 60); // Lock for 60 seconds after 5 failures
            return ['status' => 'invalid_credentials'];
        }
        
        // Clear rate limiter on success
        RateLimiter::clear($rateLimitKey);
        
        // Find admin user (do NOT auto-create for security)
        $user = User::where('email', $adminEmail)->first();
        
        if (!$user) {
            \Log::warning('Admin user not found in database', ['email' => $adminEmail]);
            return ['status' => 'invalid_credentials'];
        }
        
        // Ensure user has admin role
        if ($user->role !== 'admin') {
            $user->role = 'admin';
            $user->save();
        }
        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }
        
        // Create token with longer expiration for admin (24 hours)
        $token = $user->createToken('admin_auth_token', [], now()->addHours(24))->plainTextToken;
        
        \Log::info('Admin login successful', ['user_id' => $user->id]);
        
        return ['status' => 'ok', 'token' => $token, 'user' => $user];
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

        // Reset failed attempts if session has expired (2 hours = token expiration time)
        // Only reset if user previously had tokens but they're all expired (session expired)
        // This prevents resetting during active login attempts or for users who never logged in
        $tokenExpirationHours = 2;
        $mostRecentToken = DB::table('personal_access_tokens')
            ->where('tokenable_id', $user->id)
            ->where('tokenable_type', 'App\Models\User')
            ->orderBy('created_at', 'desc')
            ->first();
        
        // Only reset if:
        // - User has tokens (has logged in before), AND
        // - Most recent token is older than expiration time (session expired)
        // This ensures we don't reset for new users or during active login attempts
        if ($mostRecentToken) {
            $tokenAge = now()->diffInHours($mostRecentToken->created_at);
            if ($tokenAge >= $tokenExpirationHours && $user->failed_login_attempts > 0) {
                \Log::info('Resetting failed login attempts due to session expiration', [
                    'user_id' => $user->id,
                    'failed_attempts' => $user->failed_login_attempts,
                    'token_age_hours' => $tokenAge,
                ]);
                $user->failed_login_attempts = 0;
                $user->save();
            }
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
            // Always send a new temporary password when attempts exceed 5
            // This ensures users always have a fresh password if they lost the previous email
            $this->sendTemporaryPassword($user);
            return [
                'status' => 'fifth_attempt', // Use same status as 5th attempt
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
        // NOTE: Never log the temporary password - security risk!
        \Log::info('Attempting to send temporary password email', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'mail_driver' => config('mail.default'),
        ]);
        
        try {
            // Use the notification which properly formats the email with blade template
            $user->notify(new TemporaryPasswordNotification($temporaryPassword));
            
            \Log::info('Temporary password email sent successfully via notification', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send temporary password email', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to ensure we know if email fails
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
