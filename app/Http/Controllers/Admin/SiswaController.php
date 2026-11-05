<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string) $request->query('cari', ''));
        $kelas = (string) $request->query('kelas', '');
        $angkatan = (string) $request->query('angkatan', '');
        $status = (string) $request->query('status', '');

        $query = Student::query();

        if ($cari !== '') {
            $query->where(function ($builder) use ($cari) {
                $builder->where('nama_lengkap', 'like', "%{$cari}%")
                    ->orWhere('nis', 'like', "%{$cari}%")
                    ->orWhere('nisn', 'like', "%{$cari}%")
                    ->orWhere('nama_wali', 'like', "%{$cari}%")
                    ->orWhere('telepon_utama', 'like', "%{$cari}%")
                    ->orWhere('telepon_cadangan', 'like', "%{$cari}%");
            });
        }
        if ($kelas !== '') {
            $query->where('kelas', $kelas);
        }
        if ($angkatan !== '') {
            $query->where('angkatan', $angkatan);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        $daftar = $query->orderBy('nama_lengkap')->paginate(15)->withQueryString();

        $kelasOpsi = collect(Sekolah::daftarKelas())
            ->merge(Student::query()->distinct()->orderBy('kelas')->pluck('kelas'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $angkatanOpsi = Student::query()
            ->distinct()
            ->orderByDesc('angkatan')
            ->pluck('angkatan')
            ->filter()
            ->values();

        return view('admin.siswa', [
            'daftar' => $daftar,
            'kelasOpsi' => $kelasOpsi,
            'angkatanOpsi' => $angkatanOpsi,
            'cari' => $cari,
            'kelas' => $kelas,
            'angkatan' => $angkatan,
            'status' => $status,
            'totalSiswa' => Student::count(),
            'totalAktif' => Student::where('status', 'Aktif')->count(),
            'totalKelas' => Student::query()->distinct()->count('kelas'),
            'totalAngkatan' => Student::query()->distinct()->count('angkatan'),
        ]);
    }

    public function simpan(Request $request)
    {
        $id = $request->filled('id') ? (int) $request->input('id') : null;

        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:students,id'],
            'nama_lengkap' => ['required', 'string', 'max:120'],
            'nis' => ['required', 'string', 'max:30', Rule::unique('students', 'nis')->ignore($id)],
            'nisn' => ['nullable', 'string', 'max:30', Rule::unique('students', 'nisn')->ignore($id)],
            'kelas' => ['required', 'string', 'max:30'],
            'angkatan' => ['required', 'string', 'max:10'],
            'tanggal_masuk' => ['required', 'date'],
            'target_tanggal_lulus' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
            'nama_wali' => ['required', 'string', 'max:120'],
            'hubungan_wali' => ['required', 'in:Ayah,Ibu,Wali,Orang Tua'],
            'telepon_utama' => ['required', 'string', 'max:30'],
            'telepon_cadangan' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:Aktif,Lulus,Pindah,Nonaktif'],
        ], [], [
            'nama_lengkap' => 'Nama lengkap',
            'nis' => 'NIS',
            'nisn' => 'NISN',
            'nama_wali' => 'nama orang tua/wali',
            'hubungan_wali' => 'hubungan wali',
            'telepon_utama' => 'Kontak 1',
            'telepon_cadangan' => 'Kontak 2',
            'tanggal_masuk' => 'tanggal masuk',
            'target_tanggal_lulus' => 'target tanggal lulus',
        ]);

        $data['nisn'] = ($data['nisn'] ?? null) ?: null;
        $data['telepon_cadangan'] = ($data['telepon_cadangan'] ?? null) ?: null;
        $data['alamat'] = ($data['alamat'] ?? null) ?: null;
        $data['target_tanggal_lulus'] = ($data['target_tanggal_lulus'] ?? null) ?: null;
        unset($data['id']);

        if ($id !== null) {
            Student::findOrFail($id)->update($data);

            return back()->with('sukses', 'Data siswa berhasil diperbarui.');
        }

        Student::create($data);

        return back()->with('sukses', 'Data siswa berhasil ditambahkan.');
    }

    public function hapus(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:students,id'],
        ]);

        Student::findOrFail($data['id'])->delete();

        return back()->with('sukses', 'Data siswa dihapus.');
    }
}
