<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Sekolah;
use App\Support\XlsxExport;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $semua = Sekolah::absensiGuru();
        [$tanggal, $bulan, $tahun] = $this->periode($request, $semua);
        $cari = $this->queryString($request, 'cari');
        $status = $this->queryString($request, 'status');

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
            'data' => $paginator,
            'ringkasan' => [
                'hadir' => $jumlah('Hadir'),
                'terlambat' => $jumlah('Terlambat'),
                'izin' => $jumlah('Izin') + $jumlah('Sakit'),
                'curiga' => $jumlah('Mencurigakan'),
            ],
        ]);
    }

    public function verifikasi(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer'],
            'status' => ['required', 'in:Disetujui,Ditolak'],
        ]);

        $semua = Sekolah::absensiGuru();
        foreach ($semua as $i => $a) {
            if ((int) ($a['id'] ?? 0) === (int) $request->input('id')) {
                $semua[$i]['verifikasi'] = $request->input('status');
            }
        }
        Sekolah::put('absensi_guru', $semua);

        return back()->with('sukses', 'Verifikasi absensi disimpan.');
    }

    public function status(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'status' => ['required', 'in:'.implode(',', Sekolah::STATUS_ABSENSI)],
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
                $a['verifikasi'] ?? '-',
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
            ['Nama Guru', 'NIP', 'Tanggal', 'Waktu', 'Status', 'Verifikasi'],
            $rows,
            [
                'title' => 'Absensi Guru',
                'subtitle' => $subtitle,
                'sheet' => 'Absensi Guru',
                'widths' => [28, 24, 14, 12, 18, 16],
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
