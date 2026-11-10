<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses route berdasarkan role pengguna.
 *
 * Contoh penggunaan pada routes:
 *   Route::middleware('role:admin')->group(...)
 *   Route::middleware('role:doctor,admin')->group(...)
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            $loginRoute = UserRole::loginRouteForRouteName($request->route()?->getName());

            return redirect()->guest($loginRoute ? route($loginRoute) : route('login'));
        }

        if (! $user->isActive()) {
            $loginRoute = $user->role?->loginRoute() ?? 'login';

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route($loginRoute)->withErrors([
                'email' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi administrator.',
            ]);
        }

        $allowed = array_map(fn (string $role) => UserRole::tryFrom($role), $roles);

        if ($user->role === null || ! in_array($user->role, $allowed, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
