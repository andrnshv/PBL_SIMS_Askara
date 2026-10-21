<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('signin')
                ->withErrors(['email' => 'Akun kamu tidak aktif. Hubungi admin.']);
        }

        if ($roles === []
            || $user->role === User::ROLE_ADMIN
            || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Kamu tidak memiliki akses ke halaman ini.');
    }
}