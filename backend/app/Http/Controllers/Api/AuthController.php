<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Notifications\VerifyEmailNotification;
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
}
