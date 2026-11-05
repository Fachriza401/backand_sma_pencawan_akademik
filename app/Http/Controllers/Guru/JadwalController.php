<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Support\Sekolah;

class JadwalController extends Controller
{
    public function index()
    {
        $user = session('user');
        $guru = Sekolah::cariGuru($user['guruId'] ?? null) ?? (Sekolah::guru()[0] ?? []);

        $milikGuru = array_values(array_filter(
            Sekolah::jadwal(),
            fn ($j) => ($j['idGuru'] ?? null) === ($guru['id'] ?? null)
        ));

        $jadwalHari = array_values(array_filter(
            $milikGuru,
            fn ($j) => ($j['hari'] ?? null) === hari_ini()
        ));
        usort($jadwalHari, fn ($a, $b) => ((int) $a['jamKe']) <=> ((int) $b['jamKe']));

        return view('guru.jadwal', [
            'guru' => $guru,
            'jadwalHari' => $jadwalHari,
            'semuaJadwal' => $milikGuru,
            'hari' => Sekolah::HARI,
            'jam' => Sekolah::jamPelajaran(),
        ]);
    }
}
