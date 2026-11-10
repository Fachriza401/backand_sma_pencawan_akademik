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
        $absensi = collect(Sekolah::absensiGuru());
        $tanggalHariIni = now()->toDateString();
        $awalMinggu = now()->startOfWeek(Carbon::MONDAY);
        $akhirMinggu = $awalMinggu->copy()->addDays(6);
        $awalBulan = now()->startOfMonth();
        $guruAktif = collect(Sekolah::guru())
            ->filter(fn (array $guru) => ($guru['status'] ?? 'Aktif') === 'Aktif');
        $idGuruAktif = $guruAktif->pluck('id')->filter()->all();

        $perGuruHariIni = $absensi
            ->filter(fn (array $item) => ($item['tgl'] ?? null) === $tanggalHariIni
                && in_array($item['guruId'] ?? null, $idGuruAktif, true))
            ->sortBy('id')
            ->groupBy('guruId')
            ->map(fn ($records) => $records->last());
        $jumlahHariIni = fn (string $status) => $perGuruHariIni
            ->filter(fn (array $item) => ($item['status'] ?? null) === $status)
            ->count();

        $belumHadir = $guruAktif->filter(function (array $guru) use ($perGuruHariIni) {
            $presensi = $perGuruHariIni->get($guru['id'] ?? '');

            return ! $presensi || in_array($presensi['status'] ?? null, ['Belum Absen', 'Belum Hadir', 'Alpa'], true);
        })->count();

        $hariMinggu = collect(range(0, 6))->map(function (int $offset) use ($awalMinggu, $absensi, $idGuruAktif) {
            $tanggal = $awalMinggu->copy()->addDays($offset);
            $records = $absensi
                ->filter(fn (array $item) => ($item['tgl'] ?? null) === $tanggal->toDateString()
                    && in_array($item['guruId'] ?? null, $idGuruAktif, true))
                ->sortBy('id')
                ->groupBy('guruId')
                ->map(fn ($guruRecords) => $guruRecords->last())
                ->values();

            return [
                'label' => $tanggal->locale('id')->translatedFormat('D'),
                'Hadir' => $records->where('status', 'Hadir')->count(),
                'Terlambat' => $records->where('status', 'Terlambat')->count(),
                'Izin' => $records->where('status', 'Izin')->count(),
                'Sakit' => $records->where('status', 'Sakit')->count(),
            ];
        });

        $perGuruPerHariBulanIni = $absensi
            ->filter(fn (array $item) => ($item['tgl'] ?? '') >= $awalBulan->toDateString()
                && ($item['tgl'] ?? '') <= now()->toDateString()
                && in_array($item['guruId'] ?? null, $idGuruAktif, true))
            ->sortBy('id')
            ->groupBy(fn (array $item) => ($item['tgl'] ?? '').'|'.($item['guruId'] ?? ''))
            ->map(fn ($records) => $records->last());
        $statusBulanan = collect(['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa'])
            ->mapWithKeys(fn (string $status) => [
                $status => $perGuruPerHariBulanIni->filter(fn (array $item) => ($item['status'] ?? null) === $status)->count(),
            ]);

        $weekLabels = $hariMinggu->pluck('label')->all();
        $weekSeries = [
            'Hadir' => $hariMinggu->pluck('Hadir')->all(),
            'Terlambat' => $hariMinggu->pluck('Terlambat')->all(),
            'Izin' => $hariMinggu->pluck('Izin')->all(),
            'Sakit' => $hariMinggu->pluck('Sakit')->all(),
        ];
        $monthSeries = $statusBulanan->values()->all();
        $weekIsDemo = collect($weekSeries)->flatten()->sum() === 0 && app()->environment('local', 'testing');
        $monthIsDemo = $statusBulanan->sum() === 0 && app()->environment('local', 'testing');

        if ($weekIsDemo) {
            $weekSeries = [
                'Hadir' => [8, 10, 9, 11, 7, 0, 0],
                'Terlambat' => [2, 1, 2, 1, 3, 0, 0],
                'Izin' => [1, 0, 1, 1, 0, 0, 0],
                'Sakit' => [0, 1, 0, 0, 1, 0, 0],
            ];
        }

        if ($monthIsDemo) {
            $monthSeries = [106, 18, 8, 4, 2];
        }

        $guruById = $guruAktif->keyBy('id');
        $recent = $absensi
            ->filter(fn (array $item) => isset($guruById[$item['guruId'] ?? '']))
            ->sortByDesc(fn (array $item) => ($item['tgl'] ?? '').' '.($item['jam'] ?? '00:00'))
            ->take(8)
            ->values()
            ->map(fn (array $item) => $item + ['guru' => $guruById[$item['guruId']]['nama'] ?? '-'])
            ->all();

        return view('admin.dashboard', [
            'tanggal' => $tanggalHariIni,
            'totalGuru' => $guruAktif->count(),
            'hadir' => $jumlahHariIni('Hadir'),
            'terlambat' => $jumlahHariIni('Terlambat'),
            'belum' => $belumHadir,
            'izin' => $jumlahHariIni('Izin'),
            'sakit' => $jumlahHariIni('Sakit'),
            'alpa' => $jumlahHariIni('Alpa'),
            'weekLabel' => $awalMinggu->locale('id')->translatedFormat('d M').' – '.$akhirMinggu->locale('id')->translatedFormat('d M Y'),
            'weekLabels' => $weekLabels,
            'weekSeries' => $weekSeries,
            'weekIsDemo' => $weekIsDemo,
            'monthLabel' => now()->locale('id')->translatedFormat('F Y'),
            'monthSeries' => $monthSeries,
            'monthTotal' => $monthIsDemo ? array_sum($monthSeries) : $statusBulanan->sum(),
            'monthIsDemo' => $monthIsDemo,
            'recent' => $recent,
        ]);
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
