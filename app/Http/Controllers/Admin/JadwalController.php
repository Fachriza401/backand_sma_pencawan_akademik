<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Sekolah;
use Illuminate\Http\Request;

class JadwalController extends Controller
{
    public function jadwalGuru(Request $request)
    {
        $guru = Sekolah::guru();
        $aktif = array_values(array_filter($guru, fn ($g) => ($g['status'] ?? '') === 'Aktif'));
        $idGuru = (string) $request->query('guru', $aktif[0]['id'] ?? '');

        return view('admin.jadwal-guru', [
            'daftarGuru' => $guru,
            'guruAktif' => $aktif,
            'idGuru' => $idGuru,
            'jadwal' => Sekolah::jadwal(),
            'hari' => Sekolah::HARI,
            'jam' => Sekolah::jamPelajaran(),
            'daftarKelas' => Sekolah::daftarKelas(),
            'petaGuru' => collect($guru)->mapWithKeys(fn ($g) => [($g['id'] ?? '') => ($g['nama'] ?? '')])->all(),
        ]);
    }

    public function kelas(Request $request)
    {
        $kelas = (string) $request->query('kelas', Sekolah::daftarKelas()[0]);
        if (!in_array($kelas, Sekolah::daftarKelas(), true)) {
            $kelas = Sekolah::daftarKelas()[0];
        }

        $guru = Sekolah::guru();

        return view('admin.jadwal-kelas', [
            'daftarKelas' => Sekolah::daftarKelas(),
            'kelas' => $kelas,
            'jadwal' => Sekolah::jadwal(),
            'hari' => Sekolah::HARI,
            'jam' => Sekolah::jamPelajaran(),
            'daftarGuru' => $guru,
            'petaGuru' => collect($guru)->mapWithKeys(fn ($g) => [($g['id'] ?? '') => ($g['nama'] ?? '')])->all(),
        ]);
    }

    public function simpanGuru(Request $request)
    {
        $data = $this->validasi($request, true);
        $jadwal = Sekolah::jadwal();
        $idx = $request->input('idx');
        $idx = ($idx === null || $idx === '') ? null : (int) $idx;

        if ($bentrok = $this->cekBentrokGuru($jadwal, $data, $idx)) {
            return back()->withErrors(['jadwal' => $bentrok])->withInput();
        }

        if ($idx !== null && isset($jadwal[$idx])) {
            $jadwal[$idx] = array_merge($jadwal[$idx], $data);
            $pesan = 'Jadwal diperbarui.';
        } else {
            $jadwal[] = $data;
            $pesan = 'Jadwal ditambahkan.';
        }

        Sekolah::put('jadwal', array_values($jadwal));

        return back()->with('sukses', $pesan);
    }

    public function hapusGuru(Request $request)
    {
        $idx = (int) $request->input('idx');
        $jadwal = Sekolah::jadwal();
        if (isset($jadwal[$idx])) {
            array_splice($jadwal, $idx, 1);
            Sekolah::put('jadwal', array_values($jadwal));
        }

        return back()->with('sukses', 'Slot jadwal dihapus.');
    }

    public function simpanKelas(Request $request)
    {
        $data = $this->validasi($request, false);
        $jadwal = Sekolah::jadwal();
        $idx = $request->input('idx');
        $idx = ($idx === null || $idx === '') ? null : (int) $idx;

        if ($bentrok = $this->cekBentrokKelas($jadwal, $data, $idx)) {
            return back()->withErrors(['jadwal' => $bentrok])->withInput();
        }

        if ($idx !== null && isset($jadwal[$idx])) {
            $jadwal[$idx] = array_merge($jadwal[$idx], $data);
            $pesan = 'Slot diperbarui.';
        } else {
            $jadwal[] = $data;
            $pesan = 'Slot ditambahkan.';
        }

        Sekolah::put('jadwal', array_values($jadwal));

        return back()->with('sukses', $pesan);
    }

    public function hapusKelas(Request $request)
    {
        return $this->hapusGuru($request);
    }

    private function validasi(Request $request, bool $perluGuru): array
    {
        $rules = [
            'idx' => ['nullable', 'integer', 'min:0'],
            'hari' => ['required', 'in:' . implode(',', Sekolah::HARI)],
            'jamKe' => ['required', 'integer', 'min:1', 'max:8'],
            'kelas' => ['required', 'string', 'max:30'],
            'ruang' => ['required', 'string', 'max:30'],
            'mapel' => ['required', 'string', 'max:80'],
            'status' => ['nullable', 'in:Regular,Digantikan,Kosong'],
            'idGuruPengganti' => ['nullable', 'string', 'required_if:status,Digantikan'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
        if ($perluGuru) {
            $rules['idGuru'] = ['required', 'string'];
        } else {
            $rules['idGuru'] = ['required', 'string'];
        }

        $data = $request->validate($rules, [], [
            'jamKe' => 'Jam ke-',
            'idGuru' => 'Guru',
            'mapel' => 'Mata pelajaran',
        ]);

        $data['jamKe'] = (int) $data['jamKe'];
        $data['status'] = $data['status'] ?? 'Regular';
        $data['idGuruPengganti'] = $data['status'] === 'Digantikan' ? ($data['idGuruPengganti'] ?: null) : null;
        $data['catatan'] = $data['catatan'] ?? '';
        unset($data['idx']);

        return $data;
    }

    private function guruEfektif(array $slot): ?string
    {
        $status = $slot['status'] ?? 'Regular';
        if ($status === 'Kosong') {
            return null;
        }
        if ($status === 'Digantikan' && ! empty($slot['idGuruPengganti'])) {
            return $slot['idGuruPengganti'];
        }

        return $slot['idGuru'] ?? null;
    }

    private function cekBentrokGuru(array $jadwal, array $d, ?int $abaikan): ?string
    {
        $guruBaru = $this->guruEfektif($d);

        foreach ($jadwal as $i => $s) {
            if ($i === $abaikan) {
                continue;
            }
            if (($s['hari'] ?? null) !== $d['hari'] || (int) ($s['jamKe'] ?? 0) !== $d['jamKe']) {
                continue;
            }
            if ($guruBaru !== null && $this->guruEfektif($s) === $guruBaru) {
                return 'Bentrok! Guru tersebut sudah mengajar ' . ($s['kelas'] ?? '-') . ' pada jam ini.';
            }
            if (($s['kelas'] ?? null) === $d['kelas']) {
                return 'Bentrok! Kelas ' . $d['kelas'] . ' sudah diajar ' . ($s['mapel'] ?? '-') . ' pada jam ini.';
            }
        }

        return null;
    }

    private function cekBentrokKelas(array $jadwal, array $d, ?int $abaikan): ?string
    {
        $guruBaru = $this->guruEfektif($d);

        foreach ($jadwal as $i => $s) {
            if ($i === $abaikan) {
                continue;
            }
            if (($s['hari'] ?? null) !== $d['hari'] || (int) ($s['jamKe'] ?? 0) !== $d['jamKe']) {
                continue;
            }
            if (($s['kelas'] ?? null) === $d['kelas']) {
                return 'Bentrok! Kelas ini sudah terisi ' . ($s['mapel'] ?? '-') . '.';
            }
            if (($d['ruang'] ?? '-') !== '-' && ($s['ruang'] ?? null) === $d['ruang']) {
                return 'Bentrok! Ruang ' . $d['ruang'] . ' dipakai ' . ($s['kelas'] ?? '-') . ' (' . ($s['mapel'] ?? '-') . ').';
            }
            if ($guruBaru !== null && $this->guruEfektif($s) === $guruBaru) {
                return 'Bentrok! Guru tersebut sudah mengajar ' . ($s['kelas'] ?? '-') . ' pada jam ini.';
            }
        }

        return null;
    }
}
