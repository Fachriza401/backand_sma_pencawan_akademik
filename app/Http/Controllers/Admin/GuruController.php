<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\GuruImportReader;
use App\Support\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GuruController extends Controller
{
    public function index()
    {
        $akunGuru = [];
        foreach (Sekolah::akun() as $akun) {
            if (($akun['role'] ?? null) === 'guru' && isset($akun['guruId'])) {
                $akunGuru[$akun['guruId']] = [
                    'user' => $akun['user'] ?? '',
                    'email' => $akun['email'] ?? '',
                ];
            }
        }

        return view('admin.guru', [
            'daftar' => Sekolah::guru(),
            'akunGuru' => $akunGuru,
        ]);
    }

    public function simpan(Request $request)
    {
        $akunGuruSaatIni = collect(Sekolah::akun())->firstWhere('guruId', $request->input('id'));
        $passwordWajib = ! $request->filled('id') || $akunGuruSaatIni === null;

        $validator = Validator::make($request->all(), [
            'id' => ['nullable', 'string', 'max:20'],
            'nip' => ['required', 'string', 'min:10', 'max:30'],
            'nama' => ['required', 'string', 'max:120'],
            'mapel' => ['required', 'string', 'max:80'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'username' => ['required', 'string', 'min:3', 'max:80'],
            'email' => ['required', 'email', 'max:120'],
            'password' => [$passwordWajib ? 'required' : 'nullable', 'string', 'min:8', 'max:120'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_foto' => ['nullable', 'boolean'],
        ], [], [
            'nip' => 'NIP',
            'nama' => 'Nama',
            'mapel' => 'Mata pelajaran',
            'username' => 'Username',
            'email' => 'Email',
            'foto' => 'Foto profil',
        ]);

        $validator->after(function ($validator) use ($request) {
            $id = (string) $request->input('id', '');
            $akunSaatIni = collect(Sekolah::akun())->firstWhere('guruId', $id);

            if ($id !== '' && ! Sekolah::cariGuru($id)) {
                $validator->errors()->add('id', 'Data guru tidak ditemukan.');

                return;
            }

            foreach (Sekolah::guru() as $guru) {
                if (($guru['id'] ?? null) !== $id && ($guru['nip'] ?? null) === $request->input('nip')) {
                    $validator->errors()->add('nip', 'NIP sudah digunakan.');
                    break;
                }
            }

            foreach (Sekolah::akun() as $akun) {
                if (($akun['guruId'] ?? null) === $id && $akunSaatIni) {
                    continue;
                }

                $username = strtolower((string) $request->input('username'));
                $email = strtolower((string) $request->input('email'));
                $existingUsername = strtolower((string) ($akun['user'] ?? ''));
                $existingEmail = strtolower((string) ($akun['email'] ?? ''));

                if ($username === $existingUsername || $username === $existingEmail) {
                    $validator->errors()->add('username', 'Username sudah digunakan.');
                }
                if ($email === $existingUsername || $email === $existingEmail) {
                    $validator->errors()->add('email', 'Email sudah digunakan.');
                }
            }
        });

        $data = $validator->validate();
        $daftar = Sekolah::guru();
        $id = $data['id'] ?? null;
        $akun = Sekolah::akun();
        $akunIndex = null;
        foreach ($akun as $index => $account) {
            if (($account['guruId'] ?? null) === $id && $id !== null) {
                $akunIndex = $index;
                break;
            }
        }

        $fotoBaru = $this->simpanFoto($request);
        $hapusFoto = $request->boolean('hapus_foto');
        $fotoLama = '';

        if ($id) {
            foreach ($daftar as $index => $guru) {
                if (($guru['id'] ?? null) === $id) {
                    $fotoLama = (string) ($guru['foto'] ?? '');
                    $fotoFinal = $fotoBaru ?? ($hapusFoto ? '' : $fotoLama);
                    $daftar[$index] = array_merge($guru, [
                        'nip' => $data['nip'],
                        'nama' => $data['nama'],
                        'mapel' => $data['mapel'],
                        'status' => $data['status'],
                        'foto' => $fotoFinal,
                    ]);
                    break;
                }
            }
            $pesan = 'Data guru diperbarui.';
        } else {
            do {
                $id = 'G'.Str::upper(Str::random(8));
            } while (Sekolah::cariGuru($id));

            $daftar[] = [
                'id' => $id,
                'nip' => $data['nip'],
                'nama' => $data['nama'],
                'mapel' => $data['mapel'],
                'status' => $data['status'],
                'foto' => $fotoBaru ?? '',
            ];
            $pesan = 'Guru baru ditambahkan.';
        }

        $account = $akunIndex === null ? [] : $akun[$akunIndex];
        $fotoAkun = $fotoBaru ?? ($hapusFoto ? '' : (string) ($account['foto'] ?? ''));

        $account = array_merge($account, [
            'user' => $data['username'],
            'email' => $data['email'],
            'role' => 'guru',
            'nama' => $data['nama'],
            'guruId' => $id,
            'foto' => $fotoAkun,
            'aktif' => $data['status'] === 'Aktif',
        ]);
        if ($request->filled('password')) {
            $account['pass'] = Hash::make($data['password']);
        }
        if ($akunIndex === null) {
            $akun[] = $account;
        } else {
            $akun[$akunIndex] = $account;
        }

        Sekolah::put('guru', array_values($daftar));
        Sekolah::put('akun', array_values($akun));

        if (($fotoBaru !== null || $hapusFoto) && $fotoLama !== '') {
            $this->hapusFotoLama($fotoLama);
        }

        return back()->with('sukses', $pesan);
    }

    private function simpanFoto(Request $request): ?string
    {
        if (! $request->hasFile('foto')) {
            return null;
        }

        $folder = public_path('uploads/guru');
        File::ensureDirectoryExists($folder);

        $file = $request->file('foto');
        $nama = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        $file->move($folder, $nama);

        return 'uploads/guru/'.$nama;
    }

    private function hapusFotoLama(string $foto): void
    {
        $foto = ltrim($foto, '/');
        if (! str_starts_with($foto, 'uploads/guru/')) {
            return;
        }

        $path = public_path($foto);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function template()
    {
        $headers = ['nip', 'nama', 'mapel', 'status', 'username', 'email', 'password'];
        $content = "\xEF\xBB\xBF".implode(',', $headers)."\r\n";

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-data-guru.csv"',
        ]);
    }

    public function impor(Request $request, GuruImportReader $reader)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:2048'],
        ], [], ['file' => 'File impor']);

        if (! in_array(strtolower($request->file('file')->getClientOriginalExtension()), ['csv', 'xlsx'], true)) {
            return back()->withErrors(['file' => 'Format file harus .xlsx atau .csv.']);
        }

        try {
            $rows = $reader->read($request->file('file'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        if ($rows === []) {
            return back()->withErrors(['file' => 'File tidak berisi data guru.']);
        }

        $daftar = Sekolah::guru();
        $akun = Sekolah::akun();
        $nipTerpakai = [];
        $idTerpakai = [];
        foreach ($daftar as $guru) {
            $nipTerpakai[(string) ($guru['nip'] ?? '')] = true;
            $idTerpakai[(string) ($guru['id'] ?? '')] = true;
        }
        $identitasTerpakai = [];
        foreach ($akun as $account) {
            foreach (['user', 'email'] as $field) {
                $value = strtolower((string) ($account[$field] ?? ''));
                if ($value !== '') {
                    $identitasTerpakai[$value] = true;
                }
            }
        }

        $errors = [];
        $validatedRows = [];
        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $validator = Validator::make($row, [
                'nip' => ['required', 'string', 'min:10', 'max:30'],
                'nama' => ['required', 'string', 'max:120'],
                'mapel' => ['required', 'string', 'max:80'],
                'status' => ['required', 'in:Aktif,Nonaktif'],
                'username' => ['required', 'string', 'min:3', 'max:80'],
                'email' => ['required', 'email', 'max:120'],
                'password' => ['required', 'string', 'min:8', 'max:120'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "Baris {$line}: {$message}";
                }

                continue;
            }

            $data = $validator->validated();
            $username = strtolower($data['username']);
            $email = strtolower($data['email']);
            if (isset($nipTerpakai[$data['nip']])) {
                $errors[] = "Baris {$line}: NIP sudah digunakan.";
            }
            if (isset($identitasTerpakai[$username])) {
                $errors[] = "Baris {$line}: Username sudah digunakan.";
            }
            if (isset($identitasTerpakai[$email])) {
                $errors[] = "Baris {$line}: Email sudah digunakan.";
            }
            if ($username === $email) {
                $errors[] = "Baris {$line}: Username dan email harus berbeda.";
            }

            $nipTerpakai[$data['nip']] = true;
            $identitasTerpakai[$username] = true;
            $identitasTerpakai[$email] = true;
            $validatedRows[] = $data;
        }

        if ($errors !== []) {
            return back()->withErrors(['file' => implode(' ', $errors)]);
        }

        foreach ($validatedRows as $data) {
            do {
                $id = 'G'.Str::upper(Str::random(8));
            } while (isset($idTerpakai[$id]));
            $idTerpakai[$id] = true;

            $daftar[] = [
                'id' => $id,
                'nip' => $data['nip'],
                'nama' => $data['nama'],
                'mapel' => $data['mapel'],
                'status' => $data['status'],
                'foto' => '',
            ];
            $akun[] = [
                'user' => $data['username'],
                'email' => $data['email'],
                'pass' => Hash::make($data['password']),
                'role' => 'guru',
                'nama' => $data['nama'],
                'guruId' => $id,
                'foto' => '',
                'aktif' => $data['status'] === 'Aktif',
            ];
        }

        Sekolah::put('guru', array_values($daftar));
        Sekolah::put('akun', array_values($akun));

        return back()->with('sukses', count($validatedRows).' guru dan akun berhasil diimpor.');
    }

    public function hapus(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'string', 'max:20'],
        ]);

        if (! Sekolah::arsipkanGuru($data['id'])) {
            return back()->withErrors(['guru' => 'Data guru tidak ditemukan.']);
        }

        return back()->with('sukses', 'Guru diarsipkan. Jadwal dan riwayat presensi tetap tersimpan.');
    }

    public function nonaktif(Request $request)
    {
        return $this->hapus($request);
    }
}
