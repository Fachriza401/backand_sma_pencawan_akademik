<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Support\Sekolah;

class DashboardController extends Controller
{
    public function index()
    {
        $user = session('user');
        $guru = Sekolah::cariGuru($user['guruId'] ?? null);
        abort_if(! $guru || ($guru['status'] ?? null) !== 'Aktif', 403);
        $presensi = Sekolah::presensiHariIni($guru['id'] ?? '');

        $jadwalHari = array_values(array_filter(
            Sekolah::jadwal(),
            fn ($j) => ($j['idGuru'] ?? null) === ($guru['id'] ?? null) && ($j['hari'] ?? null) === hari_ini()
        ));
        usort($jadwalHari, fn ($a, $b) => ((int) $a['jamKe']) <=> ((int) $b['jamKe']));

        $jam = (int) date('H');
        $sapa = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : 'Selamat sore');
        $pengumuman = array_values(array_filter(
            Sekolah::pengumuman(),
            fn ($item) => ($item['published'] ?? false) === true
        ));
        usort($pengumuman, fn ($a, $b) => strcmp(
            (string) ($b['publishedAt'] ?? $b['updatedAt'] ?? ''),
            (string) ($a['publishedAt'] ?? $a['updatedAt'] ?? '')
        ));
        return view('guru.dashboard', [
            'guru' => $guru,
            'presensi' => $presensi,
            'jadwalHari' => $jadwalHari,
            'sapaan' => $sapa,
            'sekolah' => Sekolah::pengaturan(),
            'jam' => Sekolah::jamPelajaran(),
            'fiturUnggulanAktif' => (bool) (Sekolah::pengaturan()['fiturUnggulanGuruAktif'] ?? true),
            'informasiTerbaru' => array_slice($pengumuman, 0, 5),
        ]);
    }
}
