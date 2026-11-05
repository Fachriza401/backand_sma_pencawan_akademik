<?php

namespace App\Http\Middleware;

use App\Support\Sekolah;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Pastikan pengguna sudah login dan memiliki peran yang sesuai.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->session()->get('user');

        if (! is_array($user) || empty($user['role'])) {
            return redirect()->route('login');
        }

        if (($user['role'] ?? null) !== $role) {
            $destination = match ($user['role']) {
                'admin' => 'admin.dashboard',
                'guru' => 'guru.dashboard',
                default => 'login',
            };

            return redirect()->route($destination);
        }

        if ($role === 'guru') {
            $guru = Sekolah::cariGuru(isset($user['guruId']) ? (string) $user['guruId'] : null);
            if (! $guru || ($guru['status'] ?? null) !== 'Aktif') {
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['login' => 'Akun guru tidak aktif. Hubungi administrator.']);
            }
        }

        return $next($request);
    }
}
