<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Sekolah;
use App\Support\XlsxExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $daftar = Sekolah::laporanBulanan();
        $bulan = $this->pilihBulan($request, $daftar);
        $rows = $daftar[$bulan] ?? [];
        $rekap = $this->rekap($rows);
        $daftarMapel = collect(Sekolah::guru())->pluck('mapel')->filter()->unique()->sort()->values();
        $mapel = trim((string) $request->query('mapel', ''));
        if ($mapel !== '') {
            $rekap = array_values(array_filter(
                $rekap,
                fn (array $row) => ($row['guru']['mapel'] ?? '') === $mapel
            ));
        }

        $sum = fn (string $k) => array_sum(array_column($rekap, $k));
        $catatan = Sekolah::get('laporan_catatan');
        foreach ($rekap as &$row) {
            $row['catatan'] = $catatan[$bulan.'|'.($row['guruId'] ?? '')] ?? '';
        }
        unset($row);

        $chartData = ['labels' => [], 'hadir' => [], 'terlambat' => [], 'alpa' => []];
        ksort($daftar);
        foreach ($daftar as $periode => $dataBulan) {
            $rekapBulan = $this->rekap($dataBulan);
            if ($mapel !== '') {
                $rekapBulan = array_values(array_filter(
                    $rekapBulan,
                    fn (array $row) => ($row['guru']['mapel'] ?? '') === $mapel
                ));
            }

            $chartData['labels'][] = Carbon::createFromFormat('Y-m', $periode)->locale('id')->translatedFormat('M Y');
            foreach (['hadir', 'terlambat', 'alpa'] as $status) {
                $chartData[$status][] = array_sum(array_column($rekapBulan, $status));
            }
        }

        return view('admin.laporan', [
            'bulan' => $bulan,
            'daftarBulan' => $daftar,
            'daftarMapel' => $daftarMapel,
            'mapel' => $mapel,
            'rekap' => $rekap,
            'total' => [
                'hadir' => $sum('hadir'),
                'terlambat' => $sum('terlambat'),
                'izin' => $sum('izin'),
                'sakit' => $sum('sakit'),
                'alpa' => $sum('alpa'),
            ],
            'chartMonthTotals' => [
                'hadir' => $sum('hadir'),
                'terlambat' => $sum('terlambat'),
                'alpa' => $sum('alpa'),
            ],
            'chartData' => $chartData,
        ]);
    }

    public function ekspor(Request $request)
    {
        $daftar = Sekolah::laporanBulanan();
        $bulan = $this->pilihBulan($request, $daftar);
        $rows = $daftar[$bulan] ?? [];
        $rekap = $this->rekap($rows);
        $mapel = trim((string) $request->query('mapel', ''));
        if ($mapel !== '') {
            $rekap = array_values(array_filter(
                $rekap,
                fn (array $row) => ($row['guru']['mapel'] ?? '') === $mapel
            ));
        }
        $catatan = Sekolah::get('laporan_catatan');

        $data = [];
        foreach ($rekap as $index => $row) {
            $catatanGuru = $catatan[$bulan.'|'.($row['guruId'] ?? '')] ?? '';
            $data[] = [
                $index + 1,
                $row['guru']['nama'] ?? '-',
                $row['guru']['nip'] ?? '-',
                $row['hadir'],
                $row['terlambat'],
                $row['izin'],
                $row['sakit'],
                $row['alpa'],
                $row['persen'].'%',
                $catatanGuru,
            ];
        }

        $total = [
            'Total',
            '',
            '',
            array_sum(array_column($rekap, 'hadir')),
            array_sum(array_column($rekap, 'terlambat')),
            array_sum(array_column($rekap, 'izin')),
            array_sum(array_column($rekap, 'sakit')),
            array_sum(array_column($rekap, 'alpa')),
            '',
            '',
        ];

        $label = Carbon::createFromFormat('Y-m', $bulan)->locale('id')->translatedFormat('F Y');

        return XlsxExport::download(
            'laporan-kehadiran-'.$bulan.'.xlsx',
            ['No', 'Nama Guru', 'NIP', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa', 'Persentase', 'Catatan'],
            $data,
            [
                'title' => 'Laporan Kehadiran Guru',
                'subtitle' => 'Periode '.$label.' · SMA Pencawan',
                'sheet' => 'Laporan Kehadiran',
                'total' => $total,
                'widths' => [6, 28, 24, 10, 13, 10, 10, 10, 14, 32],
            ]
        );
    }

    public function simpanCatatan(Request $request)
    {
        $data = $request->validate([
            'bulan' => ['required', 'date_format:Y-m'],
            'guruId' => ['required', 'string', 'max:20'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'mapel' => ['nullable', 'string', 'max:80'],
        ], [], [
            'guruId' => 'Guru',
            'catatan' => 'Catatan',
        ]);

        $rows = Sekolah::laporanBulanan()[$data['bulan']] ?? null;
        if ($rows === null) {
            return back()->withErrors(['bulan' => 'Periode laporan tidak tersedia.']);
        }
        if (! collect($rows)->contains(fn (array $row) => ($row['guruId'] ?? null) === $data['guruId'])) {
            return back()->withErrors(['guruId' => 'Guru tidak ditemukan pada periode laporan tersebut.']);
        }

        $catatan = Sekolah::get('laporan_catatan');
        $key = $data['bulan'].'|'.$data['guruId'];
        $isi = trim((string) ($data['catatan'] ?? ''));
        if ($isi === '') {
            unset($catatan[$key]);
        } else {
            $catatan[$key] = $isi;
        }
        Sekolah::put('laporan_catatan', $catatan);

        return redirect()->route('admin.laporan', array_filter([
            'bulan' => $data['bulan'],
            'mapel' => $data['mapel'] ?? '',
        ]))->with('sukses', 'Catatan laporan berhasil disimpan.');
    }

    private function pilihBulan(Request $request, array $daftar): string
    {
        $diminta = (string) $request->query('bulan', '');
        if ($diminta !== '' && isset($daftar[$diminta])) {
            return $diminta;
        }

        return (string) (array_key_first($daftar) ?? date('Y-m'));
    }

    private function rekap(array $rows): array
    {
        $rekap = [];
        foreach ($rows as $r) {
            $g = Sekolah::cariGuru($r['guruId'] ?? null) ?? [];
            $hadir = (int) ($r['hadir'] ?? 0);
            $terlambat = (int) ($r['terlambat'] ?? 0);
            $izin = (int) ($r['izin'] ?? 0);
            $sakit = (int) ($r['sakit'] ?? 0);
            $alpa = (int) ($r['alpa'] ?? 0);
            $total = $hadir + $terlambat + $izin + $sakit + $alpa;
            $pct = $total ? (int) round(($hadir + $terlambat) / $total * 100) : 0;

            $rekap[] = [
                'guru' => $g,
                'guruId' => $r['guruId'] ?? '',
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpa' => $alpa,
                'persen' => $pct,
            ];
        }

        return $rekap;
    }
}
