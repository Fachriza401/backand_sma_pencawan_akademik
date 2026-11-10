<?php

namespace App\Http\Controllers\Guru;

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
        [$akun, $guru] = $this->profilSaatIni();

        return view('guru.profil', [
            'akun' => $akun,
            'guru' => $guru,
        ]);
    }

    public function update(Request $request)
    {
        [$akunSaatIni, $guruSaatIni] = $this->profilSaatIni();
        $validator = Validator::make($request->all(), [
            'nama' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'telepon' => ['nullable', 'string', 'max:24', 'regex:/^\+?[0-9\s().-]{8,24}$/'],
            'current_password' => ['required_with:password', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'max:120', 'confirmed'],
        ], [], [
            'nama' => 'Nama',
            'email' => 'Email',
            'telepon' => 'Nomor telepon',
            'current_password' => 'Kata sandi saat ini',
            'password' => 'Kata sandi baru',
        ]);

        $validator->after(function ($validator) use ($request, $akunSaatIni) {
            foreach (Sekolah::akun() as $akun) {
                if (($akun['user'] ?? null) === ($akunSaatIni['user'] ?? null)) {
                    continue;
                }

                if (
                    strcasecmp((string) ($akun['email'] ?? ''), (string) $request->input('email')) === 0
                    || strcasecmp((string) ($akun['user'] ?? ''), (string) $request->input('email')) === 0
                ) {
                    $validator->errors()->add('email', 'Email sudah digunakan akun lain.');
                    break;
                }
            }

            if ($request->filled('password') && ! Credential::matches(
                (string) $request->input('current_password'),
                (string) ($akunSaatIni['pass'] ?? '')
            )) {
                $validator->errors()->add('current_password', 'Kata sandi saat ini tidak sesuai.');
            }
        });

        $data = $validator->validate();
        $akunList = Sekolah::akun();
        $guruList = Sekolah::guru();
        $akunIndex = null;
        $guruIndex = null;

        foreach ($akunList as $index => $akun) {
            if (($akun['user'] ?? null) === ($akunSaatIni['user'] ?? null)) {
                $akunIndex = $index;
                break;
            }
        }
        foreach ($guruList as $index => $guru) {
            if (($guru['id'] ?? null) === ($guruSaatIni['id'] ?? null)) {
                $guruIndex = $index;
                break;
            }
        }
        abort_if($akunIndex === null || $guruIndex === null, 403);

        $nama = trim($data['nama']);
        $akunList[$akunIndex]['nama'] = $nama;
        $akunList[$akunIndex]['email'] = $data['email'];
        $guruList[$guruIndex]['nama'] = $nama;
        $guruList[$guruIndex]['telepon'] = trim((string) ($data['telepon'] ?? ''));
        if ($request->filled('password')) {
            $akunList[$akunIndex]['pass'] = Hash::make($data['password']);
        }

        Sekolah::put('akun', array_values($akunList));
        Sekolah::put('guru', array_values($guruList));

        $sessionUser = $request->session()->get('user', []);
        $sessionUser['nama'] = $nama;
        $sessionUser['email'] = $data['email'];
        $request->session()->put('user', $sessionUser);

        return redirect()->route('guru.profil')->with('sukses', 'Profil berhasil diperbarui.');
    }

    private function profilSaatIni(): array
    {
        $sessionUser = session('user', []);
        $username = (string) ($sessionUser['user'] ?? '');
        $guruId = (string) ($sessionUser['guruId'] ?? '');
        $akun = collect(Sekolah::akun())->first(fn (array $item) => ($item['role'] ?? null) === 'guru'
            && ($item['user'] ?? null) === $username
            && ($item['guruId'] ?? null) === $guruId);
        $guru = Sekolah::cariGuru($guruId);

        abort_if($username === '' || $guruId === '' || $akun === null || $guru === null, 403);

        return [$akun, $guru];
    }
}
