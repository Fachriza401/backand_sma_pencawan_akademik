<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\GuruImportReader;
use App\Support\Sekolah;
use App\Support\XlsxExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class NilaiController extends Controller
{
    public function index(Request $request)
    {
        $guru = $this->guru();
        $mapelTersedia = Sekolah::mapelDiajar($guru['id']);
        abort_if($mapelTersedia === [], 403, 'Guru belum memiliki mata pelajaran pada jadwal mengajar.');

        $mapel = (string) $request->query('mapel', $mapelTersedia[0]);
        if (! in_array($mapel, $mapelTersedia, true)) {
            $mapel = $mapelTersedia[0];
        }

        $kelasTersedia = Sekolah::kelasDiajarUntukMapel($guru['id'], $mapel);
        abort_if($kelasTersedia === [], 403, 'Guru belum memiliki jadwal mengajar.');

        $kelas = (string) $request->query('kelas', $kelasTersedia[0]);
        if (! in_array($kelas, $kelasTersedia, true)) {
            $kelas = $kelasTersedia[0];
        }

        $nilai = Sekolah::nilai($kelas, $mapel);
        foreach ($nilai as &$row) {
            $row['akhir'] = nilai_akhir($row);
        }
        unset($row);
        $catatanRapor = Sekolah::get('catatan_nilai_siswa')[$kelas.'|'.$mapel] ?? [];
        foreach ($nilai as $index => &$row) {
            $row['catatan'] = $catatanRapor[$row['siswa']] ?? ($row['catatan'] ?? '');
        }
        unset($row);

        return view('guru.nilai', [
            'guru' => $guru,
            'mapelTersedia' => $mapelTersedia,
            'mapel' => $mapel,
            'kelasTersedia' => $kelasTersedia,
            'kelas' => $kelas,
            'nilai' => $nilai,
        ]);
    }

    public function store(Request $request)
    {
        $guru = $this->guru();
        $mapelTersedia = Sekolah::mapelDiajar($guru['id']);
        abort_if($mapelTersedia === [], 403, 'Guru belum memiliki mata pelajaran pada jadwal mengajar.');

        $data = $request->validate([
            'mapel' => ['required', Rule::in($mapelTersedia)],
        ]);
        $kelasTersedia = Sekolah::kelasDiajarUntukMapel($guru['id'], $data['mapel']);
        abort_if($kelasTersedia === [], 403, 'Guru belum memiliki jadwal untuk mata pelajaran ini.');

        $kelas = $request->validate([
            'kelas' => ['required', Rule::in($kelasTersedia)],
        ])['kelas'];
        $nilaiLama = Sekolah::nilai($kelas, $data['mapel']);
        $nilaiInput = $request->validate([
            'nilai' => ['required', 'array', 'size:'.count($nilaiLama)],
            'nilai.*' => ['required', 'array'],
            'nilai.*.tugas' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai.*.kuis' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai.*.uh' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai.*.uts' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai.*.uas' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai.*.catatan' => ['nullable', 'string', 'max:500'],
        ]);
        if (array_keys($nilaiInput['nilai']) !== array_keys($nilaiLama)) {
            return back()->withErrors(['nilai' => 'Nilai harus diisi untuk setiap siswa.'])->withInput();
        }

        foreach ($nilaiLama as $index => &$row) {
            foreach (['tugas', 'kuis', 'uh', 'uts', 'uas'] as $komponen) {
                $row[$komponen] = (float) $nilaiInput['nilai'][$index][$komponen];
            }
            $row['catatan'] = trim((string) ($nilaiInput['nilai'][$index]['catatan'] ?? ''));
        }
        unset($row);

        Sekolah::simpanNilai($kelas, $data['mapel'], $nilaiLama);
        $catatanStore = Sekolah::get('catatan_nilai_siswa');
        $catatanStore[$kelas.'|'.$data['mapel']] = [];
        foreach ($nilaiLama as $row) {
            if (($row['catatan'] ?? '') !== '') {
                $catatanStore[$kelas.'|'.$data['mapel']][$row['siswa']] = $row['catatan'];
            }
        }
        Sekolah::put('catatan_nilai_siswa', $catatanStore);

        return redirect()->route('guru.nilai', ['kelas' => $kelas, 'mapel' => $data['mapel']])
            ->with('sukses', 'Nilai siswa berhasil disimpan.');
    }

    public function impor(Request $request, GuruImportReader $reader)
    {
        [$kelas, $mapel, $nilaiLama] = $this->nilaiUntukAksi($request);
        $file = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ])['file'];

        try {
            $rows = $reader->readTable($file);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }
        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'File tidak berisi data nilai.']);
        }

        $headerRowIndex = null;
        $headers = [];
        foreach (array_slice($rows, 0, 5, true) as $index => $candidate) {
            $candidateHeaders = array_map(fn ($header) => $this->normalizeGradeHeader((string) $header), $candidate);
            if (count(array_intersect(['nama', 'tugas', 'kuis', 'uh', 'uts', 'uas'], $candidateHeaders)) === 6) {
                $headerRowIndex = $index;
                $headers = $candidateHeaders;
                break;
            }
        }
        if ($headerRowIndex === null) {
            throw ValidationException::withMessages(['file' => 'Header kolom nilai tidak ditemukan pada file.']);
        }
        $rows = array_slice($rows, $headerRowIndex + 1);
        if (count($headers) !== count(array_unique($headers))) {
            throw ValidationException::withMessages(['file' => 'Nama kolom pada file tidak boleh duplikat.']);
        }
        foreach (['nama', 'tugas', 'kuis', 'uh', 'uts', 'uas'] as $requiredHeader) {
            if (! in_array($requiredHeader, $headers, true)) {
                throw ValidationException::withMessages(['file' => 'Kolom wajib tidak ditemukan: '.$requiredHeader.'.']);
            }
        }

        $studentIndexes = [];
        foreach ($nilaiLama as $index => $row) {
            $studentIndexes[mb_strtolower(trim((string) $row['siswa']))] = $index;
        }
        $updated = [];
        $gradeRows = [];
        $notes = [];
        foreach ($rows as $line => $row) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $mapped = [];
            foreach ($headers as $index => $header) {
                $mapped[$header] = trim((string) ($row[$index] ?? ''));
            }
            $studentName = mb_strtolower($mapped['nama'] ?? '');
            if (! isset($studentIndexes[$studentName])) {
                throw ValidationException::withMessages(['file' => 'Nama siswa pada baris '.($line + 2).' tidak ditemukan pada kelas ini.']);
            }
            if (isset($updated[$studentName])) {
                throw ValidationException::withMessages(['file' => 'Nama siswa duplikat pada baris '.($line + 2).'.']);
            }
            $updated[$studentName] = true;
            foreach (['tugas', 'kuis', 'uh', 'uts', 'uas'] as $component) {
                if (! is_numeric($mapped[$component] ?? null) || (float) $mapped[$component] < 0 || (float) $mapped[$component] > 100) {
                    throw ValidationException::withMessages(['file' => 'Nilai '.$component.' pada baris '.($line + 2).' harus antara 0 sampai 100.']);
                }
                $nilaiLama[$studentIndexes[$studentName]][$component] = (float) $mapped[$component];
            }
            $nilaiLama[$studentIndexes[$studentName]]['catatan'] = mb_substr($mapped['catatan'] ?? '', 0, 500);
        }

        if (count($updated) !== count($nilaiLama)) {
            throw ValidationException::withMessages(['file' => 'File harus memuat tepat satu baris untuk setiap siswa di kelas ini.']);
        }

        foreach ($nilaiLama as $row) {
            $row['akhir'] = nilai_akhir($row);
            $gradeRows[] = $row;
            if (($row['catatan'] ?? '') !== '') {
                $notes[$row['siswa']] = $row['catatan'];
            }
        }
        Sekolah::simpanNilai($kelas, $mapel, $gradeRows);
        $catatanStore = Sekolah::get('catatan_nilai_siswa');
        $catatanStore[$kelas.'|'.$mapel] = $notes;
        Sekolah::put('catatan_nilai_siswa', $catatanStore);

        return redirect()->route('guru.nilai', ['kelas' => $kelas, 'mapel' => $mapel])
            ->with('sukses', 'Nilai siswa berhasil diimpor.');
    }

    public function ekspor(Request $request)
    {
        [$kelas, $mapel, $nilai] = $this->nilaiUntukAksi($request);
        $rows = [];
        foreach ($nilai as $index => $row) {
            $rows[] = [
                $index + 1,
                $row['siswa'],
                $row['tugas'] ?? 0,
                $row['kuis'] ?? 0,
                $row['uh'] ?? 0,
                $row['uts'] ?? 0,
                $row['uas'] ?? 0,
                nilai_akhir($row),
                $row['catatan'] ?? '',
            ];
        }

        return XlsxExport::download(
            'rekap-nilai-'.$kelas.'-'.strtolower(str_replace(' ', '-', $mapel)).'.xlsx',
            ['No', 'Nama Siswa', 'Tugas', 'Kuis', 'UH', 'UTS', 'UAS', 'Nilai Akhir', 'Catatan Rapor'],
            $rows,
            [
                'title' => 'Rekap Nilai Siswa',
                'subtitle' => $mapel.' · '.$kelas,
                'sheet' => 'Rekap Nilai',
                'widths' => [7, 28, 12, 12, 12, 12, 12, 15, 40],
            ]
        );
    }

    private function nilaiUntukAksi(Request $request): array
    {
        $guru = $this->guru();
        $mapelTersedia = Sekolah::mapelDiajar($guru['id']);
        abort_if($mapelTersedia === [], 403, 'Guru belum memiliki mata pelajaran pada jadwal mengajar.');
        $mapel = $request->validate(['mapel' => ['required', Rule::in($mapelTersedia)]])['mapel'];
        $kelasTersedia = Sekolah::kelasDiajarUntukMapel($guru['id'], $mapel);
        abort_if($kelasTersedia === [], 403, 'Guru belum memiliki jadwal untuk mata pelajaran ini.');
        $kelas = $request->validate(['kelas' => ['required', Rule::in($kelasTersedia)]])['kelas'];
        $nilai = Sekolah::nilai($kelas, $mapel);
        $catatan = Sekolah::get('catatan_nilai_siswa')[$kelas.'|'.$mapel] ?? [];
        foreach ($nilai as &$row) {
            $row['catatan'] = $catatan[$row['siswa']] ?? ($row['catatan'] ?? '');
        }
        unset($row);

        return [$kelas, $mapel, $nilai];
    }

    private function normalizeGradeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/u', '', $header);

        return match ($header) {
            'namasiswa', 'nama' => 'nama',
            'ulanganharian' => 'uh',
            'catatanrapor', 'catatankompetensi', 'catatan' => 'catatan',
            default => $header,
        };
    }

    public function rapor(Request $request)
    {
        $data = $this->attendanceReportData($request);

        return view('guru.rapor', $data);
    }

    public function eksporRapor(Request $request)
    {
        $data = $this->attendanceReportData($request);
        $rekap = $data['rekap'];
        $headers = [
            'No',
            'Nama Siswa',
            'Hadir (H)',
            'Sakit (S)',
            'Izin (I)',
            'Alpa (A)',
            'Total Kehadiran',
            'Persentase Kehadiran',
        ];

        $rows = [];
        foreach ($rekap as $index => $row) {
            $rows[] = [
                $index + 1,
                $row['siswa']->nama_lengkap,
                $row['totalHadir'],
                $row['sakit'],
                $row['izin'],
                $row['alpa'],
                $row['totalKehadiran'],
                $row['persen'].'%',
            ];
        }

        $labelBulan = Carbon::createFromFormat('Y-m', $data['bulan'])->locale('id')->translatedFormat('F Y');

        return XlsxExport::download(
            'laporan-absensi-siswa-'.$data['kelas'].'-'.$data['bulan'].'.xlsx',
            $headers,
            $rows,
            [
                'title' => 'Laporan Absensi Siswa',
                'subtitle' => $data['kelas'].' · '.$labelBulan.' · '.$data['sekolah']['nama'],
                'sheet' => 'Absensi Siswa',
                'widths' => [7, 28, 12, 10, 10, 10, 18, 22],
            ]
        );
    }

    private function attendanceReportData(Request $request): array
    {
        $guru = $this->guru();
        $kelasTersedia = Sekolah::kelasDiajar($guru['id']);
        abort_if($kelasTersedia === [], 403, 'Guru belum memiliki jadwal mengajar.');
        $kelas = (string) $request->query('kelas', $kelasTersedia[0]);
        if (! in_array($kelas, $kelasTersedia, true)) {
            $kelas = $kelasTersedia[0];
        }

        $bulan = (string) $request->query('bulan', date('Y-m'));
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan) !== 1 || $bulan > date('Y-m')) {
            $bulan = date('Y-m');
        }

        $siswa = Student::query()
            ->where('kelas', $kelas)
            ->where('status', 'Aktif')
            ->orderBy('nama_lengkap')
            ->get();
        $dataAbsensi = Sekolah::rekapAbsensiSiswa($kelas, $bulan);
        $jumlahHari = (int) Carbon::createFromFormat('Y-m', $bulan)->daysInMonth;
        $rekap = $siswa->map(function ($student) use ($bulan, $jumlahHari, $dataAbsensi) {
            $counts = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0];
            for ($tanggal = 1; $tanggal <= $jumlahHari; $tanggal++) {
                $date = sprintf('%s-%02d', $bulan, $tanggal);
                if (Carbon::createFromFormat('Y-m-d', $date)->isWeekend()) {
                    continue;
                }
                $status = $dataAbsensi[$date][$student->nama_lengkap] ?? '';
                if (in_array($status, ['Hadir', 'Terlambat'], true)) {
                    $counts['hadir']++;
                } elseif (in_array($status, ['Sakit', 'Izin', 'Alpa'], true)) {
                    $counts[strtolower($status)]++;
                }
            }
            $totalTercatat = array_sum($counts);
            $persen = $totalTercatat > 0 ? (int) round($counts['hadir'] / $totalTercatat * 100) : 0;

            return [
                'siswa' => $student,
                'totalHadir' => $counts['hadir'],
                'sakit' => $counts['sakit'],
                'izin' => $counts['izin'],
                'alpa' => $counts['alpa'],
                'totalKehadiran' => $totalTercatat,
                'persen' => $persen,
            ];
        });

        $totalHadir = $rekap->sum('totalHadir');
        $totalSakit = $rekap->sum('sakit');
        $totalIzin = $rekap->sum('izin');
        $totalAlpa = $rekap->sum('alpa');
        $totalTercatat = $totalHadir + $totalSakit + $totalIzin + $totalAlpa;
        $totalKetidakhadiran = $totalSakit + $totalIzin + $totalAlpa;

        return [
            'guru' => $guru,
            'sekolah' => Sekolah::pengaturan(),
            'kelasTersedia' => $kelasTersedia,
            'kelas' => $kelas,
            'bulan' => $bulan,
            'rekap' => $rekap,
            'ringkasan' => [
                'totalSiswa' => $siswa->count(),
                'persentaseKelas' => $totalTercatat > 0 ? (int) round($totalHadir / $totalTercatat * 100) : 0,
                'totalKetidakhadiran' => $totalKetidakhadiran,
                'totalHadir' => $totalHadir,
            ],
        ];
    }

    private function guru(): array
    {
        $id = session('user.guruId');
        $guru = Sekolah::cariGuru(is_string($id) ? $id : null);
        abort_if($guru === null, 403, 'Akun guru tidak terhubung dengan data guru.');

        return $guru;
    }
}
