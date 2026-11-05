<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Support\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NilaiController extends Controller
{
    public function index(Request $request)
    {
        $guru = $this->guru();
        $kelasTersedia = Sekolah::kelasDiajar($guru['id']);
        abort_if($kelasTersedia === [], 403, 'Guru belum memiliki jadwal mengajar.');

        $kelas = (string) $request->query('kelas', $kelasTersedia[0]);
        if (!in_array($kelas, $kelasTersedia, true)) {
            $kelas = $kelasTersedia[0];
        }

        $nilai = Sekolah::nilai($kelas, $guru['mapel']);
        foreach ($nilai as &$row) {
            $row['akhir'] = nilai_akhir($row);
        }
        unset($row);

        return view('guru.nilai', [
            'guru' => $guru,
            'kelasTersedia' => $kelasTersedia,
            'kelas' => $kelas,
            'nilai' => $nilai,
        ]);
    }

    public function store(Request $request)
    {
        $guru = $this->guru();
        $kelasTersedia = Sekolah::kelasDiajar($guru['id']);
        abort_if($kelasTersedia === [], 403, 'Guru belum memiliki jadwal mengajar.');

        $data = $request->validate([
            'kelas' => ['required', Rule::in($kelasTersedia)],
        ]);
        $nilaiLama = Sekolah::nilai($data['kelas'], $guru['mapel']);
        $nilaiInput = $request->validate([
            'nilai' => ['required', 'array', 'size:' . count($nilaiLama)],
            'nilai.*' => ['required', 'array'],
            'nilai.*.tugas' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai.*.kuis' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai.*.uh' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai.*.uts' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai.*.uas' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        if (array_keys($nilaiInput['nilai']) !== array_keys($nilaiLama)) {
            return back()->withErrors(['nilai' => 'Nilai harus diisi untuk setiap siswa.'])->withInput();
        }

        foreach ($nilaiLama as $index => &$row) {
            foreach (['tugas', 'kuis', 'uh', 'uts', 'uas'] as $komponen) {
                $row[$komponen] = (float) $nilaiInput['nilai'][$index][$komponen];
            }
        }
        unset($row);

        Sekolah::simpanNilai($data['kelas'], $guru['mapel'], $nilaiLama);

        return redirect()->route('guru.nilai', ['kelas' => $data['kelas']])
            ->with('sukses', 'Nilai siswa berhasil disimpan.');
    }

    public function rapor(Request $request)
    {
        $guru = $this->guru();
        $kelasTersedia = Sekolah::kelasDiajar($guru['id']);
        abort_if($kelasTersedia === [], 403, 'Guru belum memiliki jadwal mengajar.');

        $kelas = (string) $request->query('kelas', $kelasTersedia[0]);
        if (!in_array($kelas, $kelasTersedia, true)) {
            $kelas = $kelasTersedia[0];
        }

        $nilai = Sekolah::nilai($kelas, $guru['mapel']);
        foreach ($nilai as &$row) {
            $row['akhir'] = nilai_akhir($row);
            $row['predikat'] = $row['akhir'] >= Sekolah::KKM ? 'Tuntas' : 'Belum Tuntas';
        }
        unset($row);

        return view('guru.rapor', [
            'guru' => $guru,
            'sekolah' => Sekolah::pengaturan(),
            'kelasTersedia' => $kelasTersedia,
            'kelas' => $kelas,
            'nilai' => $nilai,
        ]);
    }

    private function guru(): array
    {
        $id = session('user.guruId');
        $guru = Sekolah::cariGuru(is_string($id) ? $id : null);
        abort_if($guru === null, 403, 'Akun guru tidak terhubung dengan data guru.');

        return $guru;
    }

}
