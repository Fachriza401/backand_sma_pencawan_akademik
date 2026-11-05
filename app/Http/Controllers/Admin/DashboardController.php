<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $absensi = Sekolah::absensiGuru();
        $tanggal = collect($absensi)->pluck('tgl')->filter()->sort()->last() ?? date('Y-m-d');
        $hari = array_values(array_filter($absensi, fn ($a) => ($a['tgl'] ?? null) === $tanggal));

        $jumlah = function (string $status) use ($hari) {
            return count(array_filter($hari, fn ($a) => ($a['status'] ?? null) === $status));
        };

        $guruAktif = collect(Sekolah::guru())
            ->filter(fn (array $g) => ($g['status'] ?? 'Aktif') === 'Aktif')
            ->pluck('id');
        $sudahTercatat = collect($hari)
            ->reject(fn (array $a) => in_array($a['status'] ?? null, ['Belum Absen', 'Belum Hadir'], true))
            ->pluck('guruId')
            ->filter()
            ->unique();
        $belum = $guruAktif->diff($sudahTercatat)->count();

        $distribusi = [
            $jumlah('Hadir'),
            $jumlah('Terlambat'),
            $jumlah('Izin'),
            $jumlah('Sakit'),
            $jumlah('Mencurigakan'),
            $belum,
        ];

        $laporan = Sekolah::laporanBulanan();
        $periodeLeaderboard = collect($laporan)->keys()->sortDesc()->first();
        $labelPeriode = $periodeLeaderboard
            ? Carbon::createFromFormat('Y-m', $periodeLeaderboard)->locale('id')->translatedFormat('F Y')
            : '-';
        $leaderboard = $this->leaderboard($periodeLeaderboard ? ($laporan[$periodeLeaderboard] ?? []) : []);

        return view('admin.dashboard', [
            'tanggal' => $tanggal,
            'totalGuru' => count(Sekolah::guru()),
            'hadir' => $jumlah('Hadir'),
            'terlambat' => $jumlah('Terlambat'),
            'belum' => $belum,
            'recent' => array_reverse(array_slice($hari, -6)),
            'distribusi' => $distribusi,
            'disiplinGuru' => $leaderboard['disiplin'],
            'perhatianGuru' => $leaderboard['perhatian'],
            'periodeLeaderboard' => $labelPeriode,
        ]);
    }

    private function leaderboard(array $laporan): array
    {
        $guru = collect(Sekolah::guru())->keyBy('id');
        $stats = [];

        foreach ($laporan as $baris) {
            $id = (string) ($baris['guruId'] ?? '');
            if ($id === '' || ! $guru->has($id)) {
                continue;
            }

            $hadir = (int) ($baris['hadir'] ?? 0);
            $terlambat = (int) ($baris['terlambat'] ?? 0);
            $izin = (int) ($baris['izin'] ?? 0);
            $sakit = (int) ($baris['sakit'] ?? 0);
            $alpa = (int) ($baris['alpa'] ?? 0);

            $stats[$id] = [
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpa' => $alpa,
                'mencurigakan' => 0,
                'belum' => 0,
                'total' => $hadir + $terlambat + $izin + $sakit + $alpa,
            ];
        }

        $buatBaris = function (string $id, array $s) use ($guru): ?array {
            $profil = $guru->get($id);
            if (! $profil) {
                return null;
            }

            return array_merge($s, [
                'id' => $id,
                'nama' => $profil['nama'] ?? '-',
                'mapel' => $profil['mapel'] ?? '-',
                'foto' => $profil['foto'] ?? '',
                'persen' => $s['total'] > 0 ? (int) round(($s['hadir'] / $s['total']) * 100) : 0,
            ]);
        };

        $disiplin = collect($stats)
            ->filter(fn (array $s) => $s['hadir'] > 0
                && $s['terlambat'] === 0
                && $s['izin'] === 0
                && $s['sakit'] === 0
                && $s['alpa'] === 0)
            ->map(fn (array $s, string $id) => $buatBaris($id, $s))
            ->filter()
            ->sortByDesc(fn (array $g) => ($g['hadir'] * 1000) + $g['total'])
            ->take(5)
            ->values();

        $perhatian = collect($stats)
            ->map(function (array $s, string $id) use ($buatBaris) {
                $baris = $buatBaris($id, $s);
                if (! $baris) {
                    return null;
                }

                $baris['skor'] = ($s['terlambat'] * 2)
                    + ($s['izin'] * 2)
                    + $s['sakit']
                    + ($s['alpa'] * 4);
                $baris['masalah'] = $s['terlambat'] + $s['izin'] + $s['sakit'] + $s['alpa'];

                return $baris;
            })
            ->filter(fn (?array $g) => $g !== null && $g['skor'] > 0)
            ->sortByDesc(fn (array $g) => ($g['skor'] * 1000) + ($g['terlambat'] * 10) + $g['alpa'])
            ->take(5)
            ->values();

        return ['disiplin' => $disiplin, 'perhatian' => $perhatian];
    }

    public function toggleFeature(Request $request)
    {
        $data = $request->validate([
            'aktif' => ['required', 'boolean'],
        ]);
        Sekolah::simpanPengaturan([
            'fiturUnggulanGuruAktif' => $request->boolean('aktif'),
        ]);

        return back()->with(
            'sukses',
            $request->boolean('aktif')
                ? 'Fitur unggulan panel guru diaktifkan.'
                : 'Fitur unggulan panel guru dinonaktifkan.'
        );
    }
}
