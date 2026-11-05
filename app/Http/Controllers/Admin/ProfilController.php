<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Credential;
use App\Support\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfilController extends Controller
{
    public function index()
    {
        $akun = $this->currentAccount();
        abort_if($akun === null, 403);

        return view('admin.profil', [
            'akun' => $akun,
            'pengaturan' => Sekolah::pengaturan(),
        ]);
    }

    public function update(Request $request)
    {
        $akunSaatIni = $this->currentAccount();
        abort_if($akunSaatIni === null, 403);

        $validator = Validator::make($request->all(), [
            'nama' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'teleponAdmin' => ['nullable', 'string', 'max:24', 'regex:/^\+?[0-9\s().-]{8,24}$/'],
            'current_password' => ['required_with:password', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [], [
            'nama' => 'Nama',
            'email' => 'Email',
            'teleponAdmin' => 'Nomor WhatsApp Admin',
            'current_password' => 'Password saat ini',
            'password' => 'Password baru',
        ]);

        $validator->after(function ($validator) use ($request, $akunSaatIni) {
            foreach (Sekolah::akun() as $account) {
                if (($account['user'] ?? null) === ($akunSaatIni['user'] ?? null)) {
                    continue;
                }

                if (
                    strcasecmp((string) ($account['email'] ?? ''), (string) $request->input('email')) === 0
                    || strcasecmp((string) ($account['user'] ?? ''), (string) $request->input('email')) === 0
                ) {
                    $validator->errors()->add('email', 'Email sudah digunakan akun lain.');
                    break;
                }
            }

            if ($request->filled('password') && ! Credential::matches(
                (string) $request->input('current_password'),
                (string) ($akunSaatIni['pass'] ?? '')
            )) {
                $validator->errors()->add('current_password', 'Password saat ini tidak sesuai.');
            }
        });

        $data = $validator->validate();
        $accounts = Sekolah::akun();
        foreach ($accounts as $index => $account) {
            if (($account['user'] ?? null) !== ($akunSaatIni['user'] ?? null)) {
                continue;
            }

            $accounts[$index]['nama'] = $data['nama'];
            $accounts[$index]['email'] = $data['email'];
            if ($request->filled('password')) {
                $accounts[$index]['pass'] = Hash::make($data['password']);
            }
            $akunSaatIni = $accounts[$index];
            break;
        }

        Sekolah::put('akun', $accounts);
        Sekolah::simpanPengaturan([
            'teleponAdmin' => trim((string) ($data['teleponAdmin'] ?? '')),
        ]);
        $sessionUser = $request->session()->get('user', []);
        $sessionUser['nama'] = $data['nama'];
        $sessionUser['email'] = $data['email'];
        $request->session()->put('user', $sessionUser);

        return back()->with('sukses', 'Profil admin berhasil diperbarui.');
    }

    private function currentAccount(): ?array
    {
        $sessionUser = session('user', []);
        $username = (string) ($sessionUser['user'] ?? '');

        foreach (Sekolah::akun() as $akun) {
            if (($akun['role'] ?? null) === 'admin' && ($akun['user'] ?? null) === $username) {
                return $akun;
            }
        }

        return null;
    }
}
