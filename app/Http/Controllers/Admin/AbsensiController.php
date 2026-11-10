<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AttendanceAutomation;
use App\Support\Sekolah;
use App\Support\XlsxExport;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $otomatisasi = app(AttendanceAutomation::class);
        $alpaOtomatis = $otomatisasi->markAlpaForToday();
        $semua = Sekolah::absensiGuru();
        $statusTersedia = $this->statusTersedia();
        $statusLamaCount = count(array_filter(
            $semua,
            fn (array $record) => ! in_array($record['status'] ?? null, $statusTersedia, true)
        ));
        [$tanggal, $bulan, $tahun] = $this->periode($request, $semua);
        $cari = $this->queryString($request, 'cari');
        $status = $this->queryString($request, 'status');
        if (! in_array($status, $statusTersedia, true)) {
            $status = '';
        }

        $filtered = Sekolah::filterAbsensi($semua, $tanggal, $bulan, $tahun, $cari, $status);
        $perPage = 8;
        $page = max(1, (int) $request->query('page', 1));

        $paginator = new LengthAwarePaginator(
            array_slice($filtered, ($page - 1) * $perPage, $perPage),
            count($filtered),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $jumlah = fn (string $s) => count(array_filter($filtered, fn ($a) => ($a['status'] ?? null) === $s));

        return view('admin.absensi-guru', [
            'tanggal' => $tanggal,
            'filterTgl' => $this->queryString($request, 'tgl') ?: (($bulan || $tahun || $cari !== '' || $status !== '') ? null : $tanggal),
            'filterBulan' => $bulan,
            'filterTahun' => $tahun,
            'cari' => $cari,
            'status' => $status,
            'statusTersedia' => $statusTersedia,
            'statusLamaCount' => $statusLamaCount,
            'guruAktif' => array_values(array_filter(
                Sekolah::guru(),
                fn (array $guru) => ($guru['status'] ?? '') === 'Aktif'
            )),
            'tanggalHariIni' => now()->toDateString(),
            'jamSekarang' => now()->format('H:i'),
            'alpaOtomatis' => $alpaOtomatis,
            'batasAlpa' => $otomatisasi->cutoffForToday(),
            'data' => $paginator,
            'ringkasan' => [
                'hadir' => $jumlah('Hadir'),
                'terlambat' => $jumlah('Terlambat'),
                'izin' => $jumlah('Izin') + $jumlah('Sakit'),
                'alpa' => $jumlah('Alpa'),
            ],
        ]);
    }

    public function status(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'status' => ['required', 'in:'.implode(',', $this->statusTersedia())],
        ], [], ['status' => 'Status presensi']);

        $semua = Sekolah::absensiGuru();
        $ditemukan = false;
        foreach ($semua as $i => $a) {
            if ((int) ($a['id'] ?? 0) === (int) $data['id']) {
                $semua[$i]['status'] = $data['status'];
                $ditemukan = true;
                break;
            }
        }

        if (! $ditemukan) {
            return back()->withErrors(['absensi' => 'Data presensi tidak ditemukan.']);
        }

        Sekolah::put('absensi_guru', $semua);

        return back()->with('sukses', 'Status presensi berhasil diperbarui.');
    }

    public function tambahManual(Request $request)
    {
        $data = $request->validate([
            'guruId' => ['required', 'string', 'max:20'],
            'tgl' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'jam' => ['nullable', 'date_format:H:i'],
            'status' => ['required', Rule::in(['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa'])],
        ], [], [
            'guruId' => 'Guru',
            'tgl' => 'Tanggal presensi',
            'jam' => 'Waktu presensi',
            'status' => 'Status presensi',
        ]);

        $guru = Sekolah::cariGuru($data['guruId']);
        if (! $guru || ($guru['status'] ?? '') !== 'Aktif') {
            return back()->withErrors(['guruId' => 'Pilih guru aktif yang terdaftar.']);
        }

        if (in_array($data['status'], ['Hadir', 'Terlambat'], true) && empty($data['jam'])) {
            return back()->withErrors(['jam' => 'Waktu wajib diisi untuk status Hadir atau Terlambat.']);
        }

        $semua = Sekolah::absensiGuru();
        $sudahAda = collect($semua)->contains(
            fn (array $record) => ($record['guruId'] ?? null) === $data['guruId']
                && ($record['tgl'] ?? null) === $data['tgl']
        );
        if ($sudahAda) {
            return back()->withErrors(['guruId' => 'Guru tersebut sudah memiliki data presensi pada tanggal yang dipilih.']);
        }

        $id = collect($semua)->max(fn (array $record) => (int) ($record['id'] ?? 0)) + 1;
        $jam = in_array($data['status'], ['Hadir', 'Terlambat'], true) ? $data['jam'] : null;
        $semua[] = [
            'id' => $id,
            'tgl' => $data['tgl'],
            'guruId' => $data['guruId'],
            'jam' => $jam,
            'status' => $data['status'],
            'jarak' => null,
            'verifikasi' => '-',
            'lat' => null,
            'lng' => null,
        ];
        Sekolah::put('absensi_guru', $semua);

        return back()->with('sukses', 'Kehadiran manual berhasil ditambahkan.');
    }

    public function rapikanStatusLama()
    {
        $statusTersedia = $this->statusTersedia();
        $semua = Sekolah::absensiGuru();
        $diperbarui = 0;

        foreach ($semua as $index => $record) {
            if (! in_array($record['status'] ?? null, $statusTersedia, true)) {
                $semua[$index]['status'] = 'Belum Hadir';
                $diperbarui++;
            }
        }

        if ($diperbarui === 0) {
            return back()->with('sukses', 'Tidak ada status lama yang perlu dirapikan.');
        }

        Sekolah::put('absensi_guru', $semua);

        return back()->with('sukses', "{$diperbarui} status lama diperbarui menjadi Belum Hadir.");
    }

    public function hapus(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $semua = Sekolah::absensiGuru();
        $dihapus = null;
        $filtered = [];
        foreach ($semua as $item) {
            if ((int) ($item['id'] ?? 0) === (int) $data['id']) {
                $dihapus = $item;

                continue;
            }
            $filtered[] = $item;
        }

        if ($dihapus === null) {
            return back()->withErrors(['absensi' => 'Data presensi tidak ditemukan.']);
        }

        Sekolah::put('absensi_guru', $filtered);
        $this->hapusBuktiFoto($dihapus);

        return back()->with('sukses', 'Data presensi berhasil dihapus.');
    }

    public function ekspor(Request $request)
    {
        $request->validate([
            'tgl' => ['nullable', 'date_format:Y-m-d'],
            'bulan' => ['nullable', 'date_format:Y-m'],
            'tahun' => ['nullable', 'digits:4'],
        ]);

        $semua = Sekolah::absensiGuru();
        [$tanggal, $bulan, $tahun] = $this->periode($request, $semua);
        $cari = $this->queryString($request, 'cari');
        $status = $this->queryString($request, 'status');
        $filtered = Sekolah::filterAbsensi($semua, $tanggal, $bulan, $tahun, $cari, $status);

        $rows = [];
        foreach ($filtered as $a) {
            $g = Sekolah::cariGuru($a['guruId'] ?? null) ?? [];
            $rows[] = [
                $g['nama'] ?? '-',
                $g['nip'] ?? '-',
                $a['tgl'] ?? '',
                ! empty($a['jam']) ? date('H:i', strtotime($a['jam'])) : '',
                $a['status'] ?? '-',
            ];
        }

        $subtitle = $this->labelPeriode($tanggal, $bulan, $tahun);
        if ($status !== '') {
            $subtitle .= ' · Status: '.$status;
        }
        if ($cari !== '') {
            $subtitle .= ' · Pencarian: '.$cari;
        }

        $namaFile = 'absensi-guru-'.($tanggal ?: ($bulan ?: ($tahun ?: date('Y-m-d')))).'.xlsx';

        return XlsxExport::download(
            $namaFile,
            ['Nama Guru', 'NIP', 'Tanggal', 'Waktu', 'Status'],
            $rows,
            [
                'title' => 'Absensi Guru',
                'subtitle' => $subtitle,
                'sheet' => 'Absensi Guru',
                'widths' => [28, 24, 14, 12, 18],
            ]
        );
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function periode(Request $request, array $semua): array
    {
        $tanggal = $this->queryString($request, 'tgl') ?: null;
        $bulan = $this->queryString($request, 'bulan') ?: null;
        $tahun = $this->queryString($request, 'tahun') ?: null;

        $adaFilter = $tanggal || $bulan || $tahun
            || $this->queryString($request, 'cari') !== ''
            || $this->queryString($request, 'status') !== '';

        if (! $adaFilter) {
            $tanggal = collect($semua)->pluck('tgl')->filter()->sort()->last() ?? date('Y-m-d');
        }

        if ($bulan === '' || $bulan === null) {
            $bulan = null;
        }
        if ($tahun === '' || $tahun === null) {
            $tahun = null;
        }

        return [$tanggal, $bulan, $tahun];
    }

    private function labelPeriode(?string $tanggal, ?string $bulan, ?string $tahun): string
    {
        if ($tanggal) {
            return 'Tanggal: '.Carbon::parse($tanggal)->locale('id')->translatedFormat('d F Y');
        }
        if ($bulan) {
            return 'Bulan: '.Carbon::createFromFormat('Y-m', $bulan)->locale('id')->translatedFormat('F Y');
        }
        if ($tahun) {
            return 'Tahun: '.$tahun;
        }

        return 'Semua data presensi';
    }

    private function queryString(Request $request, string $key): string
    {
        $value = $request->query($key);

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function statusTersedia(): array
    {
        return array_values(array_diff(Sekolah::STATUS_ABSENSI, ['Mencurigakan']));
    }

    private function hapusBuktiFoto(array $item): void
    {
        foreach (bukti_foto_list($item) as $foto) {
            $foto = ltrim($foto, '/');
            if (str_starts_with($foto, 'uploads/absensi/')) {
                @unlink(public_path($foto));
            }
        }
    }
}
