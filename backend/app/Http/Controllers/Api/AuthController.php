<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginTemporaryRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
use App\Models\PasswordResetToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\TemporaryPasswordNotification;
use App\Notifications\PasswordResetNotification;
use Illuminate\Support\Str;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}
    // Registrácia
    public function register(Request $request)
    {
        // 1. Validácia dát
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', 'regex:/^[0-9]{7}@ucm\.sk$/'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ], [
            'email.regex' => 'Email musí byť v tvare: 7 číslic@ucm.sk (napr. 1234567@ucm.sk)'
        ]);

        // 2. Delegácia do service vrstvy (funkcionalita nezmenená)
        $this->authService->register($request->only('name','email','password'));
        // 3. Vrátenie JSON odpovede bez tokenu (používateľ musí najprv overiť email)
        return response()->json([
            'message' => 'Registrácia úspešná. Skontrolujte svoj e-mail a dokončite overenie účtu.',
            'requires_verification' => true
        ], 201);
    }

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'regex:/^[0-9]{7}@ucm\.sk$/'],
            'password' => 'required',
        ], [
            'email.regex' => 'Email musí byť v tvare: 7 číslic@ucm.sk (napr. 1234567@ucm.sk)'
        ]);

        $result = $this->authService->attemptLogin($request->email, $request->password);

        switch ($result['status']) {
            case 'no_user':
                return response()->json(['message' => 'Nesprávny email alebo heslo'], 401);
            case 'wrong_password':
                return response()->json([
                    'message' => 'Nesprávne heslo. Zostávajúce pokusy: ' . $result['remaining_attempts'],
                    'failed_attempts' => $result['failed_attempts'],
                    'remaining_attempts' => $result['remaining_attempts'],
                    'account_verified' => $result['account_verified'],
                    'max_attempts' => $result['max_attempts']
                ], 401);
            case 'fifth_attempt':
                return response()->json([
                    'message' => 'Príliš veľa neúspešných pokusov. Overovací e-mail bol odoslaný znova. Skontrolujte si schránku.',
                    'verification_resent' => true,
                    'failed_attempts' => $result['failed_attempts'],
                    'account_verified' => $result['account_verified'],
                    'max_attempts' => $result['max_attempts']
                ], 429);
            case 'too_many':
                return response()->json([
                    'message' => 'Príliš veľa neúspešných pokusov. Obnovte svoj účet cez odkaz v e‑maile, ktorý sme vám poslali.',
                    'too_many_attempts' => true,
                    'failed_attempts' => $result['failed_attempts'],
                    'account_verified' => $result['account_verified'],
                    'max_attempts' => $result['max_attempts']
                ], 429);
            case 'unverified':
                return response()->json([
                    'message' => 'Účet nie je overený. Skontrolujte e-mail a dokončite overenie.',
                    'requires_verification' => true
                ], 403);
            case 'ok':
                return response()->json([
                    'access_token' => $result['token'],
                    'token_type' => 'Bearer',
                    'user' => $result['user'],
                    'role' => $result['user']->role,
                ]);
        }
        return response()->json(['message' => 'Neznámy stav prihlásenia'], 500);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Odhlásenie úspešné']);
    }

    // Get current user
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    // Verify email with token
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $result = $this->authService->verifyToken($request->token);
        if ($result['status'] === 'invalid') {
            return response()->json(['message' => 'Neplatný verifikovaný token.'], 400);
        }
        if ($result['status'] === 'already') {
            return response()->json(['message' => 'E-mail už bol overený.'], 200);
        }
        if ($result['status'] === 'verified') {
            return response()->json([
                'message' => 'E-mail bol úspešne overený!',
                'user' => $result['user']
            ], 200);
        }
        return response()->json(['message' => 'Neznámy stav verifikácie'], 500);
    }

    // Update avatar
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();
        $path = $this->authService->updateAvatar($user, $request->file('avatar'));
        return response()->json([
            'message' => 'Avatar bol úspešne aktualizovaný',
            'avatar_path' => $path,
            'user' => $user
        ]);
    }

    // Update profile (name only; email change is not allowed)
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        // Only allow changing display name
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        // Explicitly forbid email change if provided
        if ($request->has('email') && $request->email !== $user->email) {
            return response()->json([
                'message' => 'Zmena e-mailu nie je povolená. Účet je viazaný na UCM e-mail.'
            ], 422);
        }

        $user->name = $request->name;
        $user->save();

        return response()->json([
            'message' => 'Profil bol úspešne aktualizovaný',
            'user' => $user
        ]);
    }

    // Update password
    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Aktuálne heslo je nesprávne.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Revoke all tokens except current
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json([
            'message' => 'Heslo bolo úspešne zmenené.',
        ]);
    }

    // Request password reset
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'regex:/^[0-9]{7}@ucm\.sk$/'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Don't reveal if email exists (security best practice)
            return response()->json([
                'message' => 'Ak účet s týmto e-mailom existuje, poslali sme ti odkaz na reset hesla.',
            ]);
        }

        // Invalidate old tokens
        PasswordResetToken::where('user_id', $user->id)
            ->where('type', 'reset')
            ->where('used', false)
            ->update(['used' => true]);

        // Create new token
        $token = Str::random(64);
        
        PasswordResetToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'type' => 'reset',
            'expires_at' => now()->addHour(),
            'ip_address' => $request->ip(),
        ]);

        $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token;
        
        $user->notify(new PasswordResetNotification($resetUrl));

        return response()->json([
            'message' => 'Ak účet s týmto e-mailom existuje, poslali sme ti odkaz na reset hesla.',
        ]);
    }

    // Reset password with token
    public function resetPassword(ResetPasswordRequest $request)
    {
        $resetToken = PasswordResetToken::where('token', $request->token)
            ->where('type', 'reset')
            ->with('user')
            ->first();

        if (!$resetToken || !$resetToken->isValid()) {
            return response()->json([
                'message' => 'Neplatný alebo expirovaný odkaz na reset hesla.',
            ], 422);
        }

        $resetToken->markAsUsed();
        
        $user = $resetToken->user;
        
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Heslo bolo úspešne zmenené. Môžeš sa teraz prihlásiť.',
        ]);
    }

    // Login with temporary password (sent after 5 failed attempts)
    public function loginWithTemporaryPassword(LoginTemporaryRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Nesprávny email alebo dočasné heslo.',
            ], 401);
        }

        $tempToken = PasswordResetToken::where('user_id', $user->id)
            ->where('type', 'temporary')
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->first();

        if (!$tempToken) {
            return response()->json([
                'message' => 'Dočasné heslo neexistuje alebo expirované.',
            ], 401);
        }

        // Verify temporary password (stored as hash)
        if (!Hash::check($request->temporary_password, $tempToken->token)) {
            return response()->json([
                'message' => 'Nesprávne dočasné heslo.',
            ], 401);
        }

        $tempToken->markAsUsed();

        // Clear rate limiter
        $throttleKey = Str::lower($user->email) . '|' . $request->ip();
        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

        // Create auth token
        $token = $user->createToken('temp-auth-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'message' => 'Prihlásenie úspešné. Odporúčame zmeniť heslo.',
            'should_change_password' => true,
        ]);
    }
}
