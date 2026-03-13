<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Return the current CSRF token.
     */
    public function csrfToken(): JsonResponse
    {
        return response()->json([
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * Authenticate a user with email/password credentials.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'], // username maps to email
            'password' => ['required', 'string'],
        ]);

        $authField = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'name';

        $attempt = [
            $authField => $credentials['username'],
            'password' => $credentials['password'],
        ];

        if (! Auth::attempt($attempt, $request->boolean('remember'))) {
            return response()->json([
                'success' => false,
                'error' => 'Identifiants invalides.',
            ], 401);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'isAuthenticated' => true,
            'message' => 'Connexion réussie.',
            'csrfToken' => csrf_token(),
            'user' => [
                'id' => $user->id,
                'username' => $user->name,
                'email' => $user->email,
                'is_staff' => $user->is_staff ?? false,
                'is_superuser' => $user->is_superuser ?? false,
            ],
        ]);
    }

    /**
     * Log the user out and invalidate the session.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'authenticated' => false,
            'isAuthenticated' => false,
            'message' => 'Déconnexion réussie.',
        ]);
    }

    /**
     * Return information about the currently authenticated user.
     */
    public function status(): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'authenticated' => false,
                'isAuthenticated' => false,
            ]);
        }

        $user = Auth::user();

        return response()->json([
            'authenticated' => true,
            'isAuthenticated' => true,
            'user' => [
                'id'       => $user->id,
                'username' => $user->name,
                'email'    => $user->email,
                'is_staff' => $user->is_staff ?? false,
                'is_superuser' => $user->is_superuser ?? false,
            ],
        ]);
    }
}
