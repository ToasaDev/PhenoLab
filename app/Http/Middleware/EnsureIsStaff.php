<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->is_staff) {
            return response()->json(['message' => 'Accès réservé au personnel.'], 403);
        }

        return $next($request);
    }
}
