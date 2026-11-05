<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Support\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PresensiController extends Controller
{
    public function index()
    {
        $user = session('user');
        $guru = Sekolah::cariGuru($user['guruId'] ?? null) ?? (Sekolah::guru()[0] ?? []);

        $riwayat = array_values(array_filter(
            Sekolah::absensiGuru(),
            fn ($a) => ($a['guruId'] ?? null) === ($guru['id'] ?? null)
        ));
        usort($riwayat, function ($a, $b) {
            return strcmp(($b['tgl'] ?? '').($b['jam'] ?? ''), ($a['tgl'] ?? '').($a['jam'] ?? ''));
        });

        return view('guru.presensi', [
            'guru' => $guru,
            'riwayat' => $riwayat,
            'sekolah' => Sekolah::pengaturan(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'jarak' => ['required', 'numeric', 'min:0'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'buktiFoto' => ['nullable', 'array', 'max:3'],
            'buktiFoto.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [], [
            'buktiFoto' => 'Bukti foto',
            'buktiFoto.*' => 'Bukti foto',
        ]);

        $buktiFoto = $this->simpanBuktiFoto($request);

        $user = session('user');
        $guru = Sekolah::cariGuru($user['guruId'] ?? null) ?? (Sekolah::guru()[0] ?? []);
        $sekolah = Sekolah::pengaturan();

        $jam = date('H:i');
        [$h, $m] = array_map('intval', explode(':', $jam));
        [$jh, $jm] = array_map('intval', explode(':', $sekolah['jamMasuk']));
        $batas = $jh * 60 + $jm + (int) $sekolah['toleransiMenit'];

        $jarak = (float) $data['jarak'];
        if ($jarak > (float) $sekolah['radius']) {
            $status = 'Mencurigakan';
            $verifikasi = 'Menunggu';
        } else {
            $status = ($h * 60 + $m) <= $batas ? 'Hadir' : 'Terlambat';
            $verifikasi = 'Menunggu';
        }

        Sekolah::catatPresensi($guru['id'] ?? '', [
            'jam' => $jam,
            'status' => $status,
            'jarak' => $jarak,
            'verifikasi' => $verifikasi,
            'lat' => (float) $data['lat'],
            'lng' => (float) $data['lng'],
            'buktiFoto' => $buktiFoto,
        ]);

        return back()->with('sukses', 'Absensi berhasil dikirim.');
    }

    private function simpanBuktiFoto(Request $request): array
    {
        $files = $request->file('buktiFoto', []);
        if (! is_array($files)) {
            $files = [$files];
        }

        $hasil = [];
        if ($files === []) {
            return $hasil;
        }

        $folder = public_path('uploads/absensi');
        File::ensureDirectoryExists($folder);

        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $nama = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
            $file->move($folder, $nama);
            $hasil[] = 'uploads/absensi/'.$nama;
        }

        return $hasil;
    }
}
