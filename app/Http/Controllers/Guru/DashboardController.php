<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Support\Sekolah;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = session('user');
        $guru = Sekolah::cariGuru($user['guruId'] ?? null);
        abort_if(! $guru || ($guru['status'] ?? null) !== 'Aktif', 403);
        $presensi = Sekolah::presensiHariIni($guru['id'] ?? '');
        $jamPelajaran = Sekolah::jamPelajaran();

        $hariAkhirPekan = Carbon::now()->dayOfWeekIso >= 6;
        $jadwalHari = $hariAkhirPekan ? [] : array_values(array_filter(
            Sekolah::jadwal(),
            fn ($j) => ($j['idGuru'] ?? null) === ($guru['id'] ?? null) && ($j['hari'] ?? null) === hari_ini()
        ));
        usort($jadwalHari, fn ($a, $b) => ((int) $a['jamKe']) <=> ((int) $b['jamKe']));
        foreach ($jadwalHari as &$jadwal) {
            $slot = collect($jamPelajaran)->firstWhere('ke', (int) ($jadwal['jamKe'] ?? 0));
            $jadwal['mulai'] = $slot['mulai'] ?? '';
            $jadwal['selesai'] = $slot['selesai'] ?? '';
        }
        unset($jadwal);

        $tanggalHariIni = Carbon::now()->toDateString();
        $attendanceStore = Sekolah::get('absensi_siswa');
        $gradeStore = Sekolah::get('nilai');
        $attendanceForDate = function (string $kelas, string $tanggal) use ($attendanceStore): array {
            $classData = $attendanceStore[$kelas] ?? [];
            if (! is_array($classData)) {
                return [];
            }
            if (isset($classData[$tanggal]) && is_array($classData[$tanggal])) {
                return $classData[$tanggal];
            }
            if ($tanggal === Carbon::now()->toDateString()) {
                $legacy = array_filter($classData, fn ($value) => is_string($value)
                    && in_array($value, ['Hadir', 'Izin', 'Sakit', 'Alpa'], true));
                if ($legacy !== []) {
                    return $legacy;
                }
            }

            return [];
        };

        $kelasHariIni = array_values(array_unique(array_map(
            fn (array $jadwal) => (string) ($jadwal['kelas'] ?? ''),
            $jadwalHari
        )));
        $kelasHariIni = array_values(array_filter($kelasHariIni, fn (string $kelas) => $kelas !== ''));
        $kelasMapelHariIni = [];
        foreach ($jadwalHari as $jadwal) {
            $key = ($jadwal['kelas'] ?? '').'|'.($jadwal['mapel'] ?? '');
            $kelasMapelHariIni[$key] = $jadwal;
        }

        $jumlahAbsensiKelasTerisi = 0;
        foreach ($kelasHariIni as $kelas) {
            if ($attendanceForDate($kelas, $tanggalHariIni) !== []) {
                $jumlahAbsensiKelasTerisi++;
            }
        }
        $jumlahNilaiTersimpan = count(array_filter(
            array_keys($kelasMapelHariIni),
            fn (string $key) => array_key_exists($key, $gradeStore) && is_array($gradeStore[$key])
        ));

        $weeklyAttendance = [
            'labels' => [],
            'hadir' => [],
            'izin' => [],
            'sakit' => [],
            'alpa' => [],
        ];
        $weekStart = Carbon::now()->startOfWeek();
        $classesTaught = Sekolah::kelasDiajar($guru['id']);
        for ($offset = 0; $offset < 7; $offset++) {
            $date = $weekStart->copy()->addDays($offset);
            $weeklyAttendance['labels'][] = $date->locale('id')->translatedFormat('D');
            $dailyTotals = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpa' => 0];
            foreach ($classesTaught as $kelas) {
                foreach ($attendanceForDate($kelas, $date->toDateString()) as $status) {
                    if (isset($dailyTotals[$status])) {
                        $dailyTotals[$status]++;
                    }
                }
            }
            foreach (['Hadir' => 'hadir', 'Izin' => 'izin', 'Sakit' => 'sakit', 'Alpa' => 'alpa'] as $status => $key) {
                $weeklyAttendance[$key][] = $dailyTotals[$status];
            }
        }

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
            'tanggalHariIni' => $tanggalHariIni,
            'jumlahKelasHariIni' => count($kelasHariIni),
            'jumlahSesiHariIni' => count($jadwalHari),
            'jumlahAbsensiKelasTerisi' => $jumlahAbsensiKelasTerisi,
            'jumlahAbsensiKelasPending' => max(0, count($kelasHariIni) - $jumlahAbsensiKelasTerisi),
            'jumlahNilaiTersimpan' => $jumlahNilaiTersimpan,
            'jumlahNilaiPending' => max(0, count($kelasMapelHariIni) - $jumlahNilaiTersimpan),
            'weeklyAttendance' => $weeklyAttendance,
            'koordinatSekolah' => [
                'lat' => (float) (Sekolah::pengaturan()['lat'] ?? 0),
                'lng' => (float) (Sekolah::pengaturan()['lng'] ?? 0),
                'radius' => (float) (Sekolah::pengaturan()['radius'] ?? 0),
            ],
        ]);
    }
}
