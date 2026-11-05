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

        $sum = fn (string $k) => array_sum(array_map(fn ($r) => (int) ($r[$k] ?? 0), $rows));

        return view('admin.laporan', [
            'bulan' => $bulan,
            'daftarBulan' => $daftar,
            'rekap' => $rekap,
            'total' => [
                'hadir' => $sum('hadir'),
                'terlambat' => $sum('terlambat'),
                'izin' => $sum('izin') + $sum('sakit'),
                'alpa' => $sum('alpa'),
            ],
            'komposisi' => [$sum('hadir'), $sum('terlambat'), $sum('izin'), $sum('sakit'), $sum('alpa')],
        ]);
    }

    public function ekspor(Request $request)
    {
        $daftar = Sekolah::laporanBulanan();
        $bulan = $this->pilihBulan($request, $daftar);
        $rows = $daftar[$bulan] ?? [];
        $rekap = $this->rekap($rows);

        $data = [];
        foreach ($rekap as $index => $row) {
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
        ];

        $label = Carbon::createFromFormat('Y-m', $bulan)->locale('id')->translatedFormat('F Y');

        return XlsxExport::download(
            'laporan-kehadiran-'.$bulan.'.xlsx',
            ['No', 'Nama Guru', 'NIP', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa', 'Persentase'],
            $data,
            [
                'title' => 'Laporan Kehadiran Guru',
                'subtitle' => 'Periode '.$label.' · SMA Pencawan',
                'sheet' => 'Laporan Kehadiran',
                'total' => $total,
                'widths' => [6, 28, 24, 10, 13, 10, 10, 10, 14],
            ]
        );
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
