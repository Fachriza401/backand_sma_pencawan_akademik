<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Support\Sekolah;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $profilKelas = [
            'X-1' => ['angkatan' => '2024', 'masuk' => '2024-07-15', 'lulus' => '2027-06-30'],
            'X-2' => ['angkatan' => '2024', 'masuk' => '2024-07-15', 'lulus' => '2027-06-30'],
            'XI IPA 1' => ['angkatan' => '2023', 'masuk' => '2023-07-17', 'lulus' => '2026-06-30'],
            'XI IPS 1' => ['angkatan' => '2023', 'masuk' => '2023-07-17', 'lulus' => '2026-06-30'],
            'XII IPA 1' => ['angkatan' => '2022', 'masuk' => '2022-07-18', 'lulus' => '2025-06-30'],
            'XII IPS 1' => ['angkatan' => '2022', 'masuk' => '2022-07-18', 'lulus' => '2025-06-30'],
        ];

        $waliLaki = ['Sutrisno', 'Ahmad Yusuf', 'Bambang Wijaya', 'Hendra Gunawan', 'Rudi Saputra', 'Firman Hidayat', 'Dedi Kurniawan', 'Agus Salim', 'Joko Santoso', 'Muhammad Rizki'];
        $waliPerempuan = ['Siti Rahayu', 'Dewi Anggraini', 'Nur Aisyah', 'Sri Wahyuni', 'Rina Marlina', 'Yuni Astuti', 'Lestari Handayani', 'Maya Sari', 'Fitriani', 'Elly Suryani'];
        $alamat = [
            'Jl. Pencawan Raya No. 12, Medan',
            'Jl. Melati No. 45, Medan',
            'Jl. Kenanga No. 8, Medan',
            'Jl. Sejahtera No. 21, Medan',
            'Jl. Cempaka No. 17, Medan',
            'Jl. Dahlia No. 33, Medan',
        ];

        $counter = 0;
        $kelasKe = 0;
        foreach (Sekolah::siswa() as $namaKelas => $namaSiswa) {
            $kelasKe++;
            $profil = $profilKelas[$namaKelas] ?? ['angkatan' => '2024', 'masuk' => '2024-07-15', 'lulus' => '2027-06-30'];

            foreach ($namaSiswa as $index => $nama) {
                $counter++;
                $lakiLaki = $index % 2 === 0;
                $hubungan = $lakiLaki ? 'Ayah' : 'Ibu';
                $wali = $lakiLaki
                    ? 'Bpk. '.$waliLaki[$index % count($waliLaki)]
                    : 'Ibu '.$waliPerempuan[$index % count($waliPerempuan)];

                $nis = $profil['angkatan'].str_pad((string) $kelasKe, 2, '0', STR_PAD_LEFT).str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
                $nisn = '00'.str_pad((string) (10000000 + $counter), 8, '0', STR_PAD_LEFT);

                Student::updateOrCreate(
                    ['nis' => $nis],
                    [
                        'nama_lengkap' => $nama,
                        'nisn' => $nisn,
                        'kelas' => $namaKelas,
                        'angkatan' => $profil['angkatan'],
                        'tanggal_masuk' => $profil['masuk'],
                        'target_tanggal_lulus' => $profil['lulus'],
                        'nama_wali' => $wali,
                        'hubungan_wali' => $hubungan,
                        'telepon_utama' => '0812'.str_pad((string) (30000000 + $counter * 137), 8, '0', STR_PAD_LEFT),
                        'telepon_cadangan' => '0853'.str_pad((string) (50000000 + $counter * 211), 8, '0', STR_PAD_LEFT),
                        'alamat' => $alamat[$index % count($alamat)],
                        'status' => 'Aktif',
                    ]
                );
            }
        }
    }
}
