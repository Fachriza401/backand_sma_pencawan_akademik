<?php

namespace App\Http\Middleware;

use App\Support\Sekolah;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class RestoreRememberedLogin
{
    public const COOKIE = 'sma_pencawan_remember';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('user')) {
            return $next($request);
        }

        $remembered = json_decode((string) $request->cookie(self::COOKIE), true);
        if (
            ! is_array($remembered)
            || ! isset($remembered['user'], $remembered['token'])
            || ! is_string($remembered['user'])
            || ! is_string($remembered['token'])
        ) {
            if ($request->cookie(self::COOKIE) !== null) {
                Cookie::queue(Cookie::forget(self::COOKIE));
            }

            return $next($request);
        }

        $account = Sekolah::cariAkun($remembered['user']);
        $expiresAt = $account['rememberExpiresAt'] ?? null;
        $tokenHash = $account['rememberTokenHash'] ?? null;
        $valid = $account
            && is_string($tokenHash)
            && hash_equals($tokenHash, hash('sha256', $remembered['token']))
            && is_string($expiresAt)
            && strtotime($expiresAt) > time()
            && ($account['aktif'] ?? true) === true
            && in_array($account['role'] ?? null, ['admin', 'guru'], true);

        if ($valid && ($account['role'] ?? null) === 'guru') {
            $teacher = Sekolah::cariGuru($account['guruId'] ?? null);
            $valid = $teacher && ($teacher['status'] ?? null) === 'Aktif';
        }

        if (! $valid) {
            Cookie::queue(Cookie::forget(self::COOKIE));

            return $next($request);
        }

        $request->session()->regenerate();
        $request->session()->put('user', [
            'user' => $account['user'],
            'email' => $account['email'] ?? '',
            'role' => $account['role'],
            'nama' => $account['nama'],
            'foto' => $account['foto'] ?? '',
            'guruId' => $account['guruId'] ?? null,
        ]);

        return $next($request);
    }
}
