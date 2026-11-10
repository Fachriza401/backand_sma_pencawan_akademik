<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RestoreRememberedLogin;
use App\Support\Credential;
use App\Support\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('user')) {
            return $this->redirectByRole();
        }

        $adminAccount = collect(Sekolah::akun())->firstWhere('role', 'admin');
        $settings = Sekolah::pengaturan();
        $adminPhone = preg_replace('/\D+/', '', (string) ($settings['teleponAdmin'] ?? ''));

        return view('auth.login', [
            'sekolah' => $settings,
            'adminEmail' => $adminAccount['email'] ?? null,
            'adminPhone' => $adminPhone,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'user' => ['required', 'string'],
            'pass' => ['required', 'string'],
        ]);

        $akun = Sekolah::cariAkun((string) $request->input('user'));
        $password = (string) $request->input('pass');
        $storedPassword = (string) ($akun['pass'] ?? '');
        $isHashedPassword = Credential::isHashed($storedPassword);
        $passwordMatches = $akun && Credential::matches($password, $storedPassword);

        if (
            ! $akun
            || ! in_array($akun['role'] ?? null, ['admin', 'guru'], true)
            || ! $passwordMatches
            || ($akun['aktif'] ?? true) !== true
        ) {
            return back()
                ->withInput($request->only('user'))
                ->withErrors(['login' => 'Nama pengguna atau kata sandi salah.']);
        }

        if (($akun['role'] ?? null) === 'guru') {
            $profilGuru = Sekolah::cariGuru($akun['guruId'] ?? null);
            if (! $profilGuru || ($profilGuru['status'] ?? null) !== 'Aktif') {
                return back()
                    ->withInput($request->only('user'))
                    ->withErrors(['login' => 'Akun guru tidak aktif. Hubungi administrator.']);
            }
        }

        if (! $isHashedPassword) {
            $accounts = Sekolah::akun();
            foreach ($accounts as &$account) {
                if (($account['user'] ?? null) === ($akun['user'] ?? null)) {
                    $account['pass'] = Hash::make($password);
                    break;
                }
            }
            unset($account);
            Sekolah::put('akun', $accounts);
            $akun['pass'] = Hash::make($password);
        }

        $user = [
            'user' => $akun['user'],
            'email' => $akun['email'] ?? '',
            'role' => $akun['role'],
            'nama' => $akun['role'] === 'admin' ? 'Admin' : $akun['nama'],
            'foto' => $akun['foto'] ?? '',
            'guruId' => $akun['guruId'] ?? null,
        ];

        $accounts = Sekolah::akun();
        foreach ($accounts as &$account) {
            if (($account['user'] ?? null) !== ($akun['user'] ?? null)) {
                continue;
            }

            unset($account['rememberTokenHash'], $account['rememberExpiresAt']);
            if (($account['role'] ?? null) === 'admin') {
                $account['lastLogin'] = [
                    'at' => now()->toIso8601String(),
                    'ip' => (string) ($request->ip() ?? '-'),
                    'device' => $this->loginDevice((string) $request->userAgent()),
                ];
            }
            if ($request->boolean('remember')) {
                $rememberToken = Str::random(64);
                $account['rememberTokenHash'] = hash('sha256', $rememberToken);
                $account['rememberExpiresAt'] = now()->addDays(30)->toIso8601String();
                Cookie::queue(cookie(
                    RestoreRememberedLogin::COOKIE,
                    json_encode(['user' => $akun['user'], 'token' => $rememberToken], JSON_THROW_ON_ERROR),
                    30 * 24 * 60,
                    '/',
                    null,
                    $request->isSecure(),
                    true,
                    false,
                    'lax'
                ));
            } else {
                Cookie::queue(Cookie::forget(RestoreRememberedLogin::COOKIE));
            }
            break;
        }
        unset($account);
        Sekolah::put('akun', $accounts);

        $request->session()->regenerate();
        $request->session()->put('user', $user);

        return $this->redirectByRole();
    }

    public function logout(Request $request)
    {
        $username = (string) $request->session()->get('user.user', '');
        if ($username !== '') {
            $accounts = Sekolah::akun();
            foreach ($accounts as &$account) {
                if (($account['user'] ?? null) === $username) {
                    unset($account['rememberTokenHash'], $account['rememberExpiresAt']);
                    break;
                }
            }
            unset($account);
            Sekolah::put('akun', $accounts);
        }

        Cookie::queue(Cookie::forget(RestoreRememberedLogin::COOKIE));
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function loginDevice(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Browser tidak diketahui',
        };
        $platform = match (true) {
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Perangkat tidak diketahui',
        };

        return $browser.' · '.$platform;
    }

    private function redirectByRole()
    {
        $user = session('user');
        if (! $user) {
            return redirect()->route('login');
        }

        return redirect()->route($user['role'] === 'admin' ? 'admin.dashboard' : 'guru.dashboard');
    }
}
