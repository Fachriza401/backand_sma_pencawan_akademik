<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Support\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class JadwalController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $guru = Sekolah::cariGuru($user['guruId'] ?? null) ?? (Sekolah::guru()[0] ?? []);
        $tanggal = $request->validate([
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
            'hari' => ['nullable', Rule::in(Sekolah::HARI)],
            'kelas' => ['nullable', 'string', 'max:80'],
        ])['tanggal'] ?? date('Y-m-d');

        $filters = $request->only(['hari', 'kelas']);
        $hariFilter = $filters['hari'] ?? null;
        if ($hariFilter) {
            $tanggal = Carbon::parse($tanggal)
                ->startOfWeek()
                ->addDays(array_search($hariFilter, Sekolah::HARI, true))
                ->toDateString();
        }

        $hariTerpilih = Carbon::parse($tanggal)->locale('id')->translatedFormat('l');
        $akhirPekan = Carbon::parse($tanggal)->dayOfWeekIso >= 6;

        $milikGuru = array_values(array_filter(
            Sekolah::jadwal(),
            fn ($j) => ($j['idGuru'] ?? null) === ($guru['id'] ?? null)
        ));

        $jadwalHari = array_values(array_filter(
            $akhirPekan ? [] : $milikGuru,
            fn ($j) => ($j['hari'] ?? null) === $hariTerpilih
                && (empty($filters['kelas']) || ($j['kelas'] ?? null) === $filters['kelas'])
        ));
        usort($jadwalHari, fn ($a, $b) => ((int) $a['jamKe']) <=> ((int) $b['jamKe']));
        $kelasTersedia = array_values(array_unique(array_filter(array_map(
            fn (array $jadwal) => $jadwal['kelas'] ?? null,
            $milikGuru
        ))));
        if (! in_array($filters['kelas'] ?? '', $kelasTersedia, true)) {
            $filters['kelas'] = '';
        }

        $tanggalCarbon = Carbon::parse($tanggal);
        $tabDates = [];
        foreach (Sekolah::HARI as $index => $hari) {
            $tabDates[$hari] = $tanggalCarbon->copy()->startOfWeek()->addDays($index)->toDateString();
        }
        $now = Carbon::now();
        $jadwalHari = array_map(function (array $jadwal) use ($tanggalCarbon, $now) {
            $slot = collect(Sekolah::jamPelajaran())->firstWhere('ke', (int) ($jadwal['jamKe'] ?? 0));
            $mulai = $slot['mulai'] ?? '';
            $selesai = $slot['selesai'] ?? '';
            $statusWaktu = 'Akan Datang';
            if ($tanggalCarbon->toDateString() < $now->toDateString()) {
                $statusWaktu = 'Selesai';
            } elseif ($tanggalCarbon->toDateString() === $now->toDateString() && $mulai !== '' && $selesai !== '') {
                $waktuMulai = Carbon::parse($tanggalCarbon->toDateString().' '.$mulai);
                $waktuSelesai = Carbon::parse($tanggalCarbon->toDateString().' '.$selesai);
                $statusWaktu = $now->lt($waktuMulai) ? 'Akan Datang' : ($now->lt($waktuSelesai) ? 'Sedang Berlangsung' : 'Selesai');
            }

            $jadwal['mulai'] = $mulai;
            $jadwal['selesai'] = $selesai;
            $jadwal['statusWaktu'] = $statusWaktu;

            return $jadwal;
        }, $jadwalHari);

        return view('guru.jadwal', [
            'guru' => $guru,
            'tanggal' => $tanggal,
            'hariTerpilih' => $hariTerpilih,
            'akhirPekan' => $akhirPekan,
            'jadwalHari' => $jadwalHari,
            'semuaJadwal' => $jadwalHari,
            'hari' => Sekolah::HARI,
            'hariFilter' => $hariFilter ?? $hariTerpilih,
            'kelasFilter' => $filters['kelas'],
            'kelasTersedia' => $kelasTersedia,
            'tabDates' => $tabDates,
            'jam' => Sekolah::jamPelajaran(),
        ]);
    }
}
