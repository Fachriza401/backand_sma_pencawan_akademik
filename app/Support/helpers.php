<?php

use App\Support\Sekolah;

if (! function_exists('tgl_indo')) {
    /** "2026-10-16" -> "16 Okt 2026". */
    function tgl_indo(?string $tgl): string
    {
        if (! $tgl) {
            return '-';
        }
        $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $ts = strtotime($tgl);
        if ($ts === false) {
            return $tgl;
        }

        return date('j', $ts).' '.$bulan[(int) date('n', $ts) - 1].' '.date('Y', $ts);
    }
}

if (! function_exists('hari_ini')) {
    /** Senin..Sabtu; Minggu dianggap Senin untuk demo. */
    function hari_ini(): string
    {
        $d = (int) date('N');

        return Sekolah::HARI[min(max($d - 1, 0), 5)];
    }
}

if (! function_exists('jam_sekarang')) {
    function jam_sekarang(): string
    {
        return date('H:i');
    }
}

if (! function_exists('badge_status')) {
    function badge_status(?string $status): string
    {
        $map = [
            'Hadir' => 'hadir',
            'Terlambat' => 'terlambat',
            'Izin' => 'izin',
            'Sakit' => 'sakit',
            'Alpa' => 'alpa',
            'Mencurigakan' => 'mencurigakan',
            'Belum Hadir' => 'netral',
            'Belum Absen' => 'netral',
            'Disetujui' => 'disetujui',
            'Menunggu' => 'menunggu',
            'Ditolak' => 'ditolak',
            'Aktif' => 'aktif',
            'Nonaktif' => 'nonaktif',
            'Regular' => 'aktif',
            'Digantikan' => 'emas',
            'Kosong' => 'netral',
            'Lulus' => 'aktif',
            'Pindah' => 'izin',
            'Penting' => 'emas',
            'Rendah' => 'izin',
            'Normal' => 'netral',
        ];
        $kelas = $map[$status] ?? 'netral';

        return '<span class="badge badge-'.$kelas.'">'.e($status ?? '-').'</span>';
    }
}

if (! function_exists('bukti_foto_list')) {
    /** Normalisasi arsip bukti foto absensi menjadi daftar URL/path. */
    function bukti_foto_list(array $item): array
    {
        $raw = $item['buktiFoto'] ?? $item['foto'] ?? [];
        if (is_string($raw)) {
            $raw = preg_split('/[\r\n,]+/', $raw) ?: [];
        }
        if (! is_array($raw)) {
            return [];
        }

        $hasil = [];
        foreach ($raw as $foto) {
            if (is_array($foto)) {
                $foto = $foto['url'] ?? $foto['path'] ?? '';
            }
            $foto = trim((string) $foto);
            if ($foto !== '') {
                $hasil[] = $foto;
            }
        }

        return array_values(array_unique($hasil));
    }
}

if (! function_exists('media_url')) {
    function media_url(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}

if (! function_exists('nilai_akhir')) {
    function nilai_akhir(array $r): int
    {
        return (int) round(
            (float) ($r['tugas'] ?? 0) * 0.2 +
            (float) ($r['kuis'] ?? 0) * 0.15 +
            (float) ($r['uh'] ?? 0) * 0.15 +
            (float) ($r['uts'] ?? 0) * 0.2 +
            (float) ($r['uas'] ?? 0) * 0.3
        );
    }
}

if (! function_exists('sedang_berlangsung')) {
    function sedang_berlangsung(int $jamKe): bool
    {
        foreach (Sekolah::jamPelajaran() as $jp) {
            if ((int) $jp['ke'] === $jamKe) {
                $kini = (int) date('H') * 60 + (int) date('i');
                [$h, $m] = array_map('intval', explode(':', $jp['mulai']));
                [$h2, $m2] = array_map('intval', explode(':', $jp['selesai']));

                return $kini >= $h * 60 + $m && $kini < $h2 * 60 + $m2;
            }
        }

        return false;
    }
}
