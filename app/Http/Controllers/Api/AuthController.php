<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Honeypot credentials. Any attempt matching one of these usernames is
     * always rejected and logged, even if a real user with this name exists
     * (we rotate the real admin via php artisan security:rotate-admin).
     */
    private const HONEYPOT_USERNAMES = [
        'admin', 'administrator', 'root', 'superuser', 'sysadmin',
        'test', 'demo', 'user', 'guest', 'webmaster',
        'phenolab', 'manager',
    ];

    private const HONEYPOT_PASSWORDS = [
        'admin123', 'admin', 'password', 'password123', '123456',
        '12345678', 'qwerty', 'letmein', 'demo', 'test',
        'changeme', 'root', 'toor', 'phenolab',
    ];

    private function isHoneypot(string $username, string $password): bool
    {
        $u = strtolower(trim($username));
        $p = trim($password);
        return in_array($u, self::HONEYPOT_USERNAMES, true)
            || in_array($p, self::HONEYPOT_PASSWORDS, true);
    }

    private function logAttempt(Request $request, string $username, string $password, string $reason, bool $honeypot): void
    {
        try {
            LoginAttempt::create([
                'ip'            => substr((string) $request->ip(), 0, 45),
                'username'      => substr($username, 0, 255),
                'password_hash' => Hash::make($password),
                'user_agent'    => substr((string) $request->userAgent(), 0, 500),
                'reason'        => $reason,
                'is_honeypot'   => $honeypot,
                'created_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('login_attempt log failed: '.$e->getMessage());
        }

        if ($honeypot) {
            $key = 'suspicious_ip:'.$request->ip();
            $count = (int) Cache::get($key, 0) + 1;
            Cache::put($key, $count, now()->addHours(24));
            Log::warning('honeypot login hit', [
                'ip' => $request->ip(),
                'username' => $username,
                'count_24h' => $count,
                'ua' => $request->userAgent(),
            ]);
        }
    }

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

        // Honeypot: connus comme leurres → toujours rejet + log, même si un
        // user matche par hasard. Petit délai aléatoire contre le bruteforce.
        if ($this->isHoneypot($credentials['username'], $credentials['password'])) {
            $this->logAttempt($request, $credentials['username'], $credentials['password'], 'honeypot', true);
            usleep(random_int(400_000, 1_200_000));
            return response()->json([
                'success' => false,
                'error' => 'Identifiants invalides.',
            ], 401);
        }

        // Si l'IP a déjà touché plusieurs fois le honeypot, on rejette
        // immédiatement toute tentative de cette IP pendant 24h.
        if ((int) Cache::get('suspicious_ip:'.$request->ip(), 0) >= 3) {
            $this->logAttempt($request, $credentials['username'], $credentials['password'], 'blocked_ip', false);
            usleep(random_int(400_000, 1_200_000));
            return response()->json([
                'success' => false,
                'error' => 'Identifiants invalides.',
            ], 401);
        }

        $authField = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'name';

        $attempt = [
            $authField => $credentials['username'],
            'password' => $credentials['password'],
        ];

        if (! Auth::attempt($attempt, $request->boolean('remember'))) {
            $this->logAttempt($request, $credentials['username'], $credentials['password'], 'invalid', false);
            return response()->json([
                'success' => false,
                'error' => 'Identifiants invalides.',
            ], 401);
        }

        $this->logAttempt($request, $credentials['username'], '', 'success', false);

        $request->session()->regenerate();

        $user = Auth::user();

        $groups = $user->groups()
            ->select('user_groups.id', 'user_groups.name', 'user_groups.slug')
            ->get()
            ->map(fn ($g) => [
                'id'   => $g->id,
                'name' => $g->name,
                'slug' => $g->slug,
                'role' => $g->pivot->role,
            ]);

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'isAuthenticated' => true,
            'message' => 'Connexion réussie.',
            'csrfToken' => csrf_token(),
            'user' => [
                'id'           => $user->id,
                'username'     => $user->name,
                'email'        => $user->email,
                'is_staff'     => $user->is_staff ?? false,
                'is_superuser' => $user->is_superuser ?? false,
                'groups'       => $groups,
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

        $groups = $user->groups()
            ->select('user_groups.id', 'user_groups.name', 'user_groups.slug')
            ->get()
            ->map(fn ($g) => [
                'id'   => $g->id,
                'name' => $g->name,
                'slug' => $g->slug,
                'role' => $g->pivot->role,
            ]);

        return response()->json([
            'authenticated' => true,
            'isAuthenticated' => true,
            'user' => [
                'id'           => $user->id,
                'username'     => $user->name,
                'email'        => $user->email,
                'is_staff'     => $user->is_staff ?? false,
                'is_superuser' => $user->is_superuser ?? false,
                'groups'       => $groups,
            ],
        ]);
    }
}
