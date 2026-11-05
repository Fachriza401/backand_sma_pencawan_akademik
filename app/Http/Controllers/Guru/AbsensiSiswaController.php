<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Support\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AbsensiSiswaController extends Controller
{
    public function index(Request $request)
    {
        $guru = $this->guru();
        $kelasTersedia = Sekolah::kelasDiajar($guru['id']);
        abort_if($kelasTersedia === [], 403, 'Guru belum memiliki jadwal mengajar.');

        $kelas = (string) $request->query('kelas', $kelasTersedia[0]);
        if (!in_array($kelas, $kelasTersedia, true)) {
            $kelas = $kelasTersedia[0];
        }

        $tanggal = (string) $request->query('tanggal', date('Y-m-d'));
        if (!$this->tanggalValid($tanggal)) {
            $tanggal = date('Y-m-d');
        }

        $siswa = Sekolah::siswaKelas($kelas);
        $status = Sekolah::absensiSiswa($kelas, $tanggal);

        return view('guru.absensi-siswa', [
            'guru' => $guru,
            'kelasTersedia' => $kelasTersedia,
            'kelas' => $kelas,
            'tanggal' => $tanggal,
            'siswa' => $siswa,
            'statusSiswa' => $status,
            'ringkasan' => [
                'Hadir' => count(array_filter($status, fn ($item) => $item === 'Hadir')),
                'Izin' => count(array_filter($status, fn ($item) => $item === 'Izin')),
                'Sakit' => count(array_filter($status, fn ($item) => $item === 'Sakit')),
                'Alpa' => count(array_filter($status, fn ($item) => $item === 'Alpa')),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $guru = $this->guru();
        $kelasTersedia = Sekolah::kelasDiajar($guru['id']);
        abort_if($kelasTersedia === [], 403, 'Guru belum memiliki jadwal mengajar.');

        $data = $request->validate([
            'kelas' => ['required', Rule::in($kelasTersedia)],
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', 'array', 'size:' . count(Sekolah::siswaKelas((string) $request->input('kelas')))],
            'status.*' => ['required', Rule::in(['Hadir', 'Izin', 'Sakit', 'Alpa'])],
        ]);

        $siswa = Sekolah::siswaKelas($data['kelas']);
        if (array_keys($data['status']) !== array_keys($siswa)) {
            return back()->withErrors(['status' => 'Status harus diisi untuk setiap siswa.'])->withInput();
        }

        $status = [];
        foreach ($siswa as $index => $nama) {
            $status[$nama] = $data['status'][$index];
        }

        Sekolah::simpanAbsensiSiswa($data['kelas'], $status, $data['tanggal']);

        return redirect()->route('guru.absensi-siswa', [
            'kelas' => $data['kelas'],
            'tanggal' => $data['tanggal'],
        ])->with('sukses', 'Absensi siswa berhasil disimpan.');
    }

    private function guru(): array
    {
        $id = session('user.guruId');
        $guru = Sekolah::cariGuru(is_string($id) ? $id : null);
        abort_if($guru === null, 403, 'Akun guru tidak terhubung dengan data guru.');

        return $guru;
    }

    private function tanggalValid(string $tanggal): bool
    {
        $timestamp = strtotime($tanggal);

        return $timestamp !== false
            && date('Y-m-d', $timestamp) === $tanggal
            && $tanggal <= date('Y-m-d');
    }
}
