<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InformasiController extends Controller
{
    public function index()
    {
        $informasi = Sekolah::pengumuman();
        usort($informasi, fn (array $a, array $b) => strcmp(
            (string) ($b['updatedAt'] ?? ''),
            (string) ($a['updatedAt'] ?? '')
        ));

        return view('admin.informasi', [
            'informasi' => $informasi,
            'daftarKelas' => Sekolah::daftarKelas(),
        ]);
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'string', 'max:40'],
            'judul' => ['required', 'string', 'max:160'],
            'isi' => ['required', 'string', 'max:10000'],
            'published' => ['nullable', 'boolean'],
            'pengirim' => ['nullable', 'string', 'max:100'],
            'targetKelas' => ['nullable', 'string', 'max:60'],
            'urgensi' => ['nullable', 'in:Rendah,Normal,Penting'],
        ], [], [
            'judul' => 'Judul',
            'isi' => 'Isi pengumuman',
            'pengirim' => 'Pengirim',
            'targetKelas' => 'Target kelas',
            'urgensi' => 'Urgensi',
        ]);

        $pengumuman = Sekolah::pengumuman();
        $id = $data['id'] ?? null;
        $index = null;
        foreach ($pengumuman as $i => $item) {
            if (($item['id'] ?? null) === $id && $id !== null) {
                $index = $i;
                break;
            }
        }

        if ($id !== null && $index === null) {
            return back()->withErrors(['informasi' => 'Informasi tidak ditemukan.'])->withInput();
        }

        $published = $request->boolean('published');
        $now = now()->toIso8601String();
        $previouslyPublished = $index !== null && ($pengumuman[$index]['published'] ?? false);
        $record = array_merge($index === null ? [] : $pengumuman[$index], [
            'id' => $id ?? (string) Str::uuid(),
            'judul' => $data['judul'],
            'isi' => $data['isi'],
            'pengirim' => trim((string) ($data['pengirim'] ?? '')),
            'targetKelas' => trim((string) ($data['targetKelas'] ?? '')),
            'urgensi' => $data['urgensi'] ?? 'Normal',
            'published' => $published,
            'updatedAt' => $now,
            'publishedAt' => $published
                ? (($previouslyPublished && ! empty($pengumuman[$index]['publishedAt'])) ? $pengumuman[$index]['publishedAt'] : $now)
                : null,
        ]);

        if ($index === null) {
            $record['createdAt'] = $now;
            $pengumuman[] = $record;
        } else {
            $pengumuman[$index] = $record;
        }

        Sekolah::put('pengumuman', array_values($pengumuman));

        return back()->with('sukses', $published ? 'Informasi dipublikasikan.' : 'Informasi disimpan sebagai draf.');
    }

    public function hapus(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'string', 'max:40'],
        ]);
        $pengumuman = Sekolah::pengumuman();
        $filtered = array_values(array_filter(
            $pengumuman,
            fn (array $item) => ($item['id'] ?? null) !== $data['id']
        ));

        if (count($filtered) === count($pengumuman)) {
            return back()->withErrors(['informasi' => 'Informasi tidak ditemukan.']);
        }

        Sekolah::put('pengumuman', $filtered);

        return back()->with('sukses', 'Informasi berhasil dihapus.');
    }
}
