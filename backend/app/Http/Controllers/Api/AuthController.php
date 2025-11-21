<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Str;

class AuthController extends Controller
{
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

        // 2. Vytvorenie používateľa
        $verificationToken = Str::random(64);
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user', // default role
            'verification_token' => $verificationToken,
            'failed_login_attempts' => 0,
        ]);

        // 3. Odoslanie verifikovaného e-mailu
        // ℹ️ UNCOMMENT the line below to enable email sending (requires Mailhog running on localhost:1025)
        $user->notify(new VerifyEmailNotification($verificationToken));

        // 4. Vytvorenie tokenu pre Sanctum
        $token = $user->createToken('auth_token', [], now()->addHours(2))->plainTextToken;

        // 5. Vrátenie JSON odpovede
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'role' => $user->role, // posielame rolu
            'message' => 'Registrácia úspešná. Skontrolujte svoj e-mail pre verifikovaný odkaz.'
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

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Increment failed login attempts if user exists
            if ($user) {
                $user->increment('failed_login_attempts');
                
                // On exactly 5th failed attempt
                if ($user->failed_login_attempts == 5) {
                    // Unified behavior: always generate a fresh verification token and resend email
                    $verificationToken = Str::random(64);
                    $user->verification_token = $verificationToken;
                    $user->save();

                    // Send email for both verified and unverified users (verified users may ignore it)
                    $user->notify(new VerifyEmailNotification($verificationToken));

                    return response()->json([
                        'message' => 'Príliš veľa neúspešných pokusov. Overovací e-mail bol odoslaný znova. Skontrolujte si schránku.',
                        'verification_resent' => true,
                        'failed_attempts' => $user->failed_login_attempts,
                        'account_verified' => (bool) $user->email_verified_at,
                        'max_attempts' => 5
                    ], 429);
                }
                // After 5 attempts, keep blocking
                else if ($user->failed_login_attempts > 5) {
                    return response()->json([
                        'message' => 'Príliš veľa neúspešných pokusov. Obnovte svoj účet cez odkaz v e‑maile, ktorý sme vám poslali.',
                        'too_many_attempts' => true,
                        'failed_attempts' => $user->failed_login_attempts,
                        'account_verified' => (bool) $user->email_verified_at,
                        'max_attempts' => 5
                    ], 429);
                }
                
                // For attempts 1-4, show remaining attempts
                $remainingAttempts = 5 - $user->failed_login_attempts;
                return response()->json([
                    'message' => 'Nesprávne heslo. Zostávajúce pokusy: ' . $remainingAttempts,
                    'failed_attempts' => $user->failed_login_attempts,
                    'remaining_attempts' => $remainingAttempts,
                    'account_verified' => (bool) $user->email_verified_at,
                    'max_attempts' => 5
                ], 401);
            }
            
            return response()->json(['message' => 'Nesprávny email alebo heslo'], 401);
        }

        // Reset failed login attempts on successful login
        $user->failed_login_attempts = 0;
        $user->save();

        $token = $user->createToken('auth_token', [], now()->addHours(2))->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'role' => $user->role, // posielame rolu
        ]);
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

        $user = User::where('verification_token', $request->token)->first();

        if (!$user) {
            return response()->json(['message' => 'Neplatný verifikovaný token.'], 400);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'E-mail už bol overený.'], 200);
        }

        $user->email_verified_at = now();
        $user->verification_token = null;
        $user->failed_login_attempts = 0;
        $user->save();

        return response()->json([
            'message' => 'E-mail bol úspešne overený!',
            'user' => $user
        ], 200);
    }

    // Update avatar
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar_path) {
            $oldPath = storage_path('app/public/' . $user->avatar_path);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        
        $user->avatar_path = $path;
        $user->save();

        return response()->json([
            'message' => 'Avatar bol úspešne aktualizovaný',
            'avatar_path' => $path,
            'user' => $user
        ]);
    }
}
