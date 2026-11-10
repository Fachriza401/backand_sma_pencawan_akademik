<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AbsensiSiswaController extends Controller
{
    public function index(Request $request)
    {
        $guru = $this->guru();
        $kelasTersedia = Sekolah::kelasDiajar($guru['id']);
        abort_if($kelasTersedia === [], 403, 'Guru belum memiliki jadwal mengajar.');

        $kelas = (string) $request->query('kelas', $kelasTersedia[0]);
        if (! in_array($kelas, $kelasTersedia, true)) {
            $kelas = $kelasTersedia[0];
        }

        $tanggal = (string) $request->query('tanggal', date('Y-m-d'));
        if (! $this->tanggalValid($tanggal)) {
            $tanggal = date('Y-m-d');
        }

        $siswa = Student::query()
            ->where('kelas', $kelas)
            ->where('status', 'Aktif')
            ->orderBy('nama_lengkap')
            ->get();
        $namaSiswa = $siswa->pluck('nama_lengkap')->all();
        $status = Sekolah::absensiSiswa($kelas, $tanggal);
        $catatanSiswa = Sekolah::get('catatan_absensi_siswa')[$kelas][$tanggal] ?? [];
        if (! is_array($catatanSiswa)) {
            $catatanSiswa = [];
        }

        return view('guru.absensi-siswa', [
            'guru' => $guru,
            'kelasTersedia' => $kelasTersedia,
            'kelas' => $kelas,
            'tanggal' => $tanggal,
            'siswa' => $siswa,
            'namaSiswa' => $namaSiswa,
            'statusSiswa' => $status,
            'catatanSiswa' => $catatanSiswa,
            'jumlahSiswa' => $siswa->count(),
            'ringkasan' => [
                'Hadir' => count(array_filter($status, fn ($item) => $item === 'Hadir')),
                'Terlambat' => count(array_filter($status, fn ($item) => $item === 'Terlambat')),
                'Izin' => count(array_filter($status, fn ($item) => $item === 'Izin')),
                'Sakit' => count(array_filter($status, fn ($item) => $item === 'Sakit')),
                'Alpa' => count(array_filter($status, fn ($item) => $item === 'Alpa')),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $guru = $this->guru();
        $kelasTersedia = Sekolah::kelasDiajar($guru['id']);
        abort_if($kelasTersedia === [], 403, 'Guru belum memiliki jadwal mengajar.');

        $data = $request->validate([
            'kelas' => ['required', Rule::in($kelasTersedia)],
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', 'array', 'size:'.Student::query()->where('kelas', (string) $request->input('kelas'))->where('status', 'Aktif')->count()],
            'status.*' => ['required', Rule::in(['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa'])],
            'catatan' => ['nullable', 'array'],
            'catatan.*' => ['nullable', 'string', 'max:240'],
        ]);

        $siswa = Student::query()
            ->where('kelas', $data['kelas'])
            ->where('status', 'Aktif')
            ->orderBy('nama_lengkap')
            ->get()
            ->values();
        $expectedIndexes = $siswa->isEmpty() ? [] : range(0, $siswa->count() - 1);
        if (array_keys($data['status']) !== $expectedIndexes) {
            return back()->withErrors(['status' => 'Status harus diisi untuk setiap siswa.'])->withInput();
        }

        $status = [];
        $catatan = [];
        $catatanInput = $data['catatan'] ?? [];
        foreach ($siswa as $index => $student) {
            $statusSiswa = $data['status'][$index];
            $status[$student->nama_lengkap] = $statusSiswa;
            $catatan[$student->nama_lengkap] = in_array($statusSiswa, ['Izin', 'Terlambat', 'Sakit'], true)
                ? trim((string) ($catatanInput[$index] ?? ''))
                : '';
        }

        Sekolah::simpanAbsensiSiswa($data['kelas'], $status, $data['tanggal']);
        $semuaCatatan = Sekolah::get('catatan_absensi_siswa');
        $semuaCatatan[$data['kelas']][$data['tanggal']] = array_filter(
            $catatan,
            fn (string $isi) => $isi !== ''
        );
        Sekolah::put('catatan_absensi_siswa', $semuaCatatan);

        return redirect()->route('guru.absensi-siswa', [
            'kelas' => $data['kelas'],
            'tanggal' => $data['tanggal'],
        ])->with('sukses', 'Absensi siswa berhasil disimpan.');
    }

    public function simpanSiswa(Request $request)
    {
        $guru = $this->guru();
        $kelasTersedia = Sekolah::kelasDiajar($guru['id']);
        abort_if($kelasTersedia === [], 403, 'Guru belum memiliki jadwal mengajar.');

        $id = $request->filled('id') ? (int) $request->input('id') : null;
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:students,id'],
            'kelas' => ['required', Rule::in($kelasTersedia)],
            'nama_lengkap' => ['required', 'string', 'max:120'],
            'nis' => ['required', 'string', 'max:30', Rule::unique('students', 'nis')->ignore($id)],
            'nisn' => ['nullable', 'string', 'max:30', Rule::unique('students', 'nisn')->ignore($id)],
            'angkatan' => ['required', 'string', 'max:10'],
            'nama_wali' => ['required', 'string', 'max:120'],
            'hubungan_wali' => ['required', 'in:Ayah,Ibu,Wali,Orang Tua'],
            'telepon_utama' => ['required', 'string', 'max:30'],
            'telepon_cadangan' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ]);

        if ($id !== null) {
            $student = Student::findOrFail($id);
            abort_unless($student->kelas === $data['kelas'] && in_array($data['kelas'], $kelasTersedia, true), 403);
            unset($data['id'], $data['kelas']);
            $student->update($data);

            return back()->with('sukses', 'Data siswa berhasil diperbarui.');
        }

        $data['tanggal_masuk'] = date('Y-m-d');
        $data['status'] = 'Aktif';
        unset($data['id']);
        Student::create($data);

        return back()->with('sukses', 'Data siswa berhasil ditambahkan.');
    }

    public function hapusSiswa(Request $request)
    {
        $guru = $this->guru();
        $kelasTersedia = Sekolah::kelasDiajar($guru['id']);
        abort_if($kelasTersedia === [], 403, 'Guru belum memiliki jadwal mengajar.');

        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:students,id'],
            'kelas' => ['required', Rule::in($kelasTersedia)],
        ]);
        $student = Student::findOrFail($data['id']);
        abort_unless($student->kelas === $data['kelas'], 403);
        $student->delete();

        return back()->with('sukses', 'Data siswa berhasil dihapus.');
    }

    private function guru(): array
    {
        $id = session('user.guruId');
        $guru = Sekolah::cariGuru(is_string($id) ? $id : null);
        abort_if($guru === null, 403, 'Akun guru tidak terhubung dengan data guru.');

        return $guru;
    }

    private function tanggalValid(string $tanggal): bool
    {
        $timestamp = strtotime($tanggal);

        return $timestamp !== false
            && date('Y-m-d', $timestamp) === $tanggal
            && $tanggal <= date('Y-m-d');
    }
}
