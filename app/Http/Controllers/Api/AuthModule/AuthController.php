<?php

namespace App\Http\Controllers\Api\AuthModule;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * This did not exist before — the frontend's src/api/auth.js has been
 * calling /login, /forgot-password, /logout this whole time with
 * nothing behind them. login() is real. forgotPassword() is a stub
 * (no email is actually sent — see comment inside). If Laravel Sanctum
 * isn't installed, login() falls back to returning the user with no
 * token, which means nothing is actually protecting your other routes
 * yet — see the note at the bottom of this file.
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        $user->load('userType');

        // If Sanctum is installed (composer require laravel/sanctum,
        // php artisan install:api), uncomment this to issue a real
        // token instead of returning the user with nothing to prove it:
        //   $token = $user->createToken('api')->plainTextToken;
        //   return response()->json(['user' => $user, 'token' => $token]);

        return response()->json(['user' => $user]);
    }

    /**
     * STUB — validates the email format and always returns success
     * (so the frontend UI works end to end), but sends NO actual email.
     * A real version needs Laravel's password reset notification system
     * wired up (Password::sendResetLink()), which requires a mail
     * driver to be configured. Tell me when you're ready for that and
     * I'll build the real version — this is intentionally not it.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        return response()->json(['message' => 'If an account exists for this email, a reset link has been sent.']);
    }

    public function logout(Request $request)
    {
        // With Sanctum: $request->user()->currentAccessToken()->delete();
        // Without it, there's no server-side session to destroy — this
        // just gives the frontend a clean 200 to clear its own state.
        return response()->json(['message' => 'Logged out.']);
    }
}