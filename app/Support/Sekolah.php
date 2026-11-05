<?php

namespace App\Support;

/**
 * Penyimpanan data aplikasi akademik SMA Pencawan.
 * Data dummy disimpan sebagai file JSON di storage/app/data agar perubahan
 * (guru, jadwal, absensi, nilai, dll.) tetap ada tanpa memerlukan database.
 */
class Sekolah
{
    public const KKM = 75;

    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    public const STATUS_ABSENSI = ['Belum Hadir', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa', 'Mencurigakan'];

    /** Data yang bisa berubah (disimpan ke JSON). */
    public static function defaults(): array
    {
        return [
            'pengaturan' => [
                'nama' => 'SMA Pencawan',
                'alamat' => 'Jl. Pencawan Raya No. 12, Medan, Sumatera Utara',
                'lat' => 3.595199,
                'lng' => 98.672199,
                'radius' => 100,
                'jamMasuk' => '07:00',
                'toleransiMenit' => 15,
                'jamPulang' => '15:30',
                'fiturUnggulanGuruAktif' => true,
                'teleponAdmin' => '',
            ],

            'pengumuman' => [],

            'akun' => [
                ['user' => 'admin', 'email' => 'admin@acsupply.com', 'pass' => 'admin123', 'role' => 'admin', 'nama' => 'Administrator', 'foto' => 'https://i.pravatar.cc/150?img=68'],
                ['user' => 'guru', 'email' => 'guru@acsupply.com', 'pass' => 'guru123', 'role' => 'guru', 'nama' => 'Budi Santoso, S.Pd.', 'guruId' => 'G01', 'foto' => 'https://i.pravatar.cc/150?img=11'],
            ],

            'guru' => [
                ['id' => 'G01', 'nip' => '198503122010011001', 'nama' => 'Budi Santoso, S.Pd.', 'mapel' => 'Matematika', 'status' => 'Aktif', 'foto' => 'https://i.pravatar.cc/150?img=11'],
                ['id' => 'G02', 'nip' => '198706152011012002', 'nama' => 'Siti Aminah, S.Pd.', 'mapel' => 'Bahasa Indonesia', 'status' => 'Aktif', 'foto' => 'https://i.pravatar.cc/150?img=47'],
                ['id' => 'G03', 'nip' => '198209232009031003', 'nama' => 'Ahmad Fauzi, M.Pd.', 'mapel' => 'Bahasa Inggris', 'status' => 'Aktif', 'foto' => 'https://i.pravatar.cc/150?img=12'],
                ['id' => 'G04', 'nip' => '199011052015031004', 'nama' => 'Dewi Lestari, S.Si.', 'mapel' => 'Biologi', 'status' => 'Aktif', 'foto' => 'https://i.pravatar.cc/150?img=32'],
                ['id' => 'G05', 'nip' => '198404182010012005', 'nama' => 'Rina Wulandari, S.Pd.', 'mapel' => 'Kimia', 'status' => 'Aktif', 'foto' => 'https://i.pravatar.cc/150?img=45'],
                ['id' => 'G06', 'nip' => '198910102014021006', 'nama' => 'Hendra Kurniawan, S.Pd.', 'mapel' => 'Fisika', 'status' => 'Aktif', 'foto' => 'https://i.pravatar.cc/150?img=13'],
                ['id' => 'G07', 'nip' => '198612242011011007', 'nama' => 'Joko Prasetyo, S.Sos.', 'mapel' => 'PPKn', 'status' => 'Aktif', 'foto' => 'https://i.pravatar.cc/150?img=14'],
                ['id' => 'G08', 'nip' => '199203032017032008', 'nama' => 'Maya Sari, S.Pd.', 'mapel' => 'Sejarah', 'status' => 'Aktif', 'foto' => 'https://i.pravatar.cc/150?img=44'],
                ['id' => 'G09', 'nip' => '198307192009031009', 'nama' => 'Rudi Hartono, S.T.', 'mapel' => 'Informatika', 'status' => 'Aktif', 'foto' => 'https://i.pravatar.cc/150?img=15'],
                ['id' => 'G10', 'nip' => '198811292012012010', 'nama' => 'Fitri Handayani, S.Pd.', 'mapel' => 'Seni Budaya', 'status' => 'Aktif', 'foto' => 'https://i.pravatar.cc/150?img=46'],
                ['id' => 'G11', 'nip' => '198502142010011011', 'nama' => 'Andi Pratama, S.Pd.', 'mapel' => 'PJOK', 'status' => 'Nonaktif', 'foto' => 'https://i.pravatar.cc/150?img=16'],
                ['id' => 'G12', 'nip' => '199105072016032012', 'nama' => 'Lina Marlina, S.Pd.', 'mapel' => 'Geografi', 'status' => 'Aktif', 'foto' => 'https://i.pravatar.cc/150?img=48'],
            ],

            'jadwal' => [
                ['idGuru' => 'G01', 'hari' => 'Senin', 'jamKe' => 1, 'kelas' => 'X-1', 'mapel' => 'Matematika', 'ruang' => 'R-101'],
                ['idGuru' => 'G01', 'hari' => 'Senin', 'jamKe' => 2, 'kelas' => 'X-2', 'mapel' => 'Matematika', 'ruang' => 'R-102'],
                ['idGuru' => 'G01', 'hari' => 'Selasa', 'jamKe' => 3, 'kelas' => 'XI IPA 1', 'mapel' => 'Matematika', 'ruang' => 'R-201'],
                ['idGuru' => 'G01', 'hari' => 'Rabu', 'jamKe' => 1, 'kelas' => 'XII IPA 1', 'mapel' => 'Matematika', 'ruang' => 'R-301'],
                ['idGuru' => 'G01', 'hari' => 'Kamis', 'jamKe' => 4, 'kelas' => 'X-1', 'mapel' => 'Matematika', 'ruang' => 'R-101'],
                ['idGuru' => 'G02', 'hari' => 'Senin', 'jamKe' => 3, 'kelas' => 'XI IPS 1', 'mapel' => 'B. Indonesia', 'ruang' => 'R-202'],
                ['idGuru' => 'G02', 'hari' => 'Selasa', 'jamKe' => 1, 'kelas' => 'X-1', 'mapel' => 'B. Indonesia', 'ruang' => 'R-101'],
                ['idGuru' => 'G02', 'hari' => 'Rabu', 'jamKe' => 2, 'kelas' => 'X-2', 'mapel' => 'B. Indonesia', 'ruang' => 'R-102'],
                ['idGuru' => 'G03', 'hari' => 'Senin', 'jamKe' => 5, 'kelas' => 'XII IPS 1', 'mapel' => 'B. Inggris', 'ruang' => 'R-302'],
                ['idGuru' => 'G03', 'hari' => 'Kamis', 'jamKe' => 2, 'kelas' => 'XI IPA 1', 'mapel' => 'B. Inggris', 'ruang' => 'R-201'],
                ['idGuru' => 'G04', 'hari' => 'Selasa', 'jamKe' => 5, 'kelas' => 'XI IPA 1', 'mapel' => 'Biologi', 'ruang' => 'Lab-1'],
                ['idGuru' => 'G04', 'hari' => 'Rabu', 'jamKe' => 4, 'kelas' => 'XII IPA 1', 'mapel' => 'Biologi', 'ruang' => 'Lab-1'],
                ['idGuru' => 'G05', 'hari' => 'Senin', 'jamKe' => 4, 'kelas' => 'XII IPA 1', 'mapel' => 'Kimia', 'ruang' => 'Lab-2'],
                ['idGuru' => 'G05', 'hari' => 'Kamis', 'jamKe' => 5, 'kelas' => 'XI IPA 1', 'mapel' => 'Kimia', 'ruang' => 'Lab-2'],
                ['idGuru' => 'G06', 'hari' => 'Selasa', 'jamKe' => 2, 'kelas' => 'X-2', 'mapel' => 'Fisika', 'ruang' => 'R-102'],
                ['idGuru' => 'G07', 'hari' => 'Rabu', 'jamKe' => 5, 'kelas' => 'X-1', 'mapel' => 'PPKn', 'ruang' => 'R-101'],
                ['idGuru' => 'G08', 'hari' => 'Jumat', 'jamKe' => 1, 'kelas' => 'XI IPS 1', 'mapel' => 'Sejarah', 'ruang' => 'R-202'],
                ['idGuru' => 'G09', 'hari' => 'Jumat', 'jamKe' => 3, 'kelas' => 'X-1', 'mapel' => 'Informatika', 'ruang' => 'Lab-Kom'],
                ['idGuru' => 'G10', 'hari' => 'Sabtu', 'jamKe' => 1, 'kelas' => 'X-2', 'mapel' => 'Seni Budaya', 'ruang' => 'R-102'],
                ['idGuru' => 'G12', 'hari' => 'Selasa', 'jamKe' => 6, 'kelas' => 'XII IPS 1', 'mapel' => 'Geografi', 'ruang' => 'R-302'],
            ],

            'absensi_guru' => [
                ['id' => 1, 'tgl' => '2026-10-16', 'guruId' => 'G01', 'jam' => '06:52', 'status' => 'Hadir', 'jarak' => 45, 'verifikasi' => 'Disetujui', 'lat' => 3.5955, 'lng' => 98.6725],
                ['id' => 2, 'tgl' => '2026-10-16', 'guruId' => 'G02', 'jam' => '06:58', 'status' => 'Hadir', 'jarak' => 62, 'verifikasi' => 'Disetujui', 'lat' => 3.5950, 'lng' => 98.6718],
                ['id' => 3, 'tgl' => '2026-10-16', 'guruId' => 'G03', 'jam' => '07:04', 'status' => 'Hadir', 'jarak' => 30, 'verifikasi' => 'Disetujui', 'lat' => 3.5953, 'lng' => 98.6720],
                ['id' => 4, 'tgl' => '2026-10-16', 'guruId' => 'G04', 'jam' => '07:19', 'status' => 'Terlambat', 'jarak' => 88, 'verifikasi' => 'Disetujui', 'lat' => 3.5948, 'lng' => 98.6730],
                ['id' => 5, 'tgl' => '2026-10-16', 'guruId' => 'G05', 'jam' => '07:08', 'status' => 'Terlambat', 'jarak' => 75, 'verifikasi' => 'Menunggu', 'lat' => 3.5957, 'lng' => 98.6715],
                ['id' => 6, 'tgl' => '2026-10-16', 'guruId' => 'G06', 'jam' => null, 'status' => 'Belum Hadir', 'jarak' => null, 'verifikasi' => '-', 'lat' => null, 'lng' => null],
                ['id' => 7, 'tgl' => '2026-10-16', 'guruId' => 'G07', 'jam' => '06:47', 'status' => 'Hadir', 'jarak' => 51, 'verifikasi' => 'Disetujui', 'lat' => 3.5952, 'lng' => 98.6722],
                ['id' => 8, 'tgl' => '2026-10-16', 'guruId' => 'G08', 'jam' => null, 'status' => 'Izin', 'jarak' => null, 'verifikasi' => 'Disetujui', 'lat' => null, 'lng' => null],
                ['id' => 9, 'tgl' => '2026-10-16', 'guruId' => 'G09', 'jam' => '06:55', 'status' => 'Hadir', 'jarak' => 39, 'verifikasi' => 'Disetujui', 'lat' => 3.5954, 'lng' => 98.6719],
                ['id' => 10, 'tgl' => '2026-10-16', 'guruId' => 'G10', 'jam' => null, 'status' => 'Sakit', 'jarak' => null, 'verifikasi' => 'Disetujui', 'lat' => null, 'lng' => null],
                ['id' => 11, 'tgl' => '2026-10-16', 'guruId' => 'G11', 'jam' => '06:40', 'status' => 'Mencurigakan', 'jarak' => 340, 'verifikasi' => 'Menunggu', 'lat' => 3.5920, 'lng' => 98.6760],
                ['id' => 12, 'tgl' => '2026-10-16', 'guruId' => 'G12', 'jam' => '06:50', 'status' => 'Hadir', 'jarak' => 58, 'verifikasi' => 'Disetujui', 'lat' => 3.5951, 'lng' => 98.6723],
                ['id' => 13, 'tgl' => '2026-10-15', 'guruId' => 'G01', 'jam' => '06:51', 'status' => 'Hadir', 'jarak' => 45, 'verifikasi' => 'Disetujui', 'lat' => 3.5955, 'lng' => 98.6725],
                ['id' => 14, 'tgl' => '2026-10-15', 'guruId' => 'G02', 'jam' => '06:57', 'status' => 'Hadir', 'jarak' => 62, 'verifikasi' => 'Disetujui', 'lat' => 3.5950, 'lng' => 98.6718],
                ['id' => 15, 'tgl' => '2026-10-15', 'guruId' => 'G03', 'jam' => '07:22', 'status' => 'Terlambat', 'jarak' => 33, 'verifikasi' => 'Disetujui', 'lat' => 3.5953, 'lng' => 98.6720],
                ['id' => 16, 'tgl' => '2026-10-15', 'guruId' => 'G04', 'jam' => '06:59', 'status' => 'Hadir', 'jarak' => 41, 'verifikasi' => 'Disetujui', 'lat' => 3.5948, 'lng' => 98.6730],
                ['id' => 17, 'tgl' => '2026-10-15', 'guruId' => 'G05', 'jam' => '06:53', 'status' => 'Hadir', 'jarak' => 75, 'verifikasi' => 'Disetujui', 'lat' => 3.5957, 'lng' => 98.6715],
                ['id' => 18, 'tgl' => '2026-10-15', 'guruId' => 'G06', 'jam' => '06:48', 'status' => 'Hadir', 'jarak' => 67, 'verifikasi' => 'Disetujui', 'lat' => 3.5950, 'lng' => 98.6721],
                ['id' => 19, 'tgl' => '2026-10-15', 'guruId' => 'G07', 'jam' => '07:16', 'status' => 'Terlambat', 'jarak' => 51, 'verifikasi' => 'Disetujui', 'lat' => 3.5952, 'lng' => 98.6722],
                ['id' => 20, 'tgl' => '2026-10-15', 'guruId' => 'G08', 'jam' => '06:45', 'status' => 'Hadir', 'jarak' => 29, 'verifikasi' => 'Disetujui', 'lat' => 3.5954, 'lng' => 98.6724],
                ['id' => 21, 'tgl' => '2026-10-14', 'guruId' => 'G01', 'jam' => '06:54', 'status' => 'Hadir', 'jarak' => 45, 'verifikasi' => 'Disetujui', 'lat' => 3.5955, 'lng' => 98.6725],
                ['id' => 22, 'tgl' => '2026-10-14', 'guruId' => 'G02', 'jam' => '06:56', 'status' => 'Hadir', 'jarak' => 62, 'verifikasi' => 'Disetujui', 'lat' => 3.5950, 'lng' => 98.6718],
                ['id' => 23, 'tgl' => '2026-10-14', 'guruId' => 'G03', 'jam' => null, 'status' => 'Sakit', 'jarak' => null, 'verifikasi' => 'Disetujui', 'lat' => null, 'lng' => null],
                ['id' => 24, 'tgl' => '2026-10-14', 'guruId' => 'G04', 'jam' => '07:20', 'status' => 'Terlambat', 'jarak' => 41, 'verifikasi' => 'Disetujui', 'lat' => 3.5948, 'lng' => 98.6730],
                ['id' => 25, 'tgl' => '2026-10-14', 'guruId' => 'G05', 'jam' => '06:52', 'status' => 'Hadir', 'jarak' => 75, 'verifikasi' => 'Disetujui', 'lat' => 3.5957, 'lng' => 98.6715],
                ['id' => 26, 'tgl' => '2026-10-14', 'guruId' => 'G06', 'jam' => '06:49', 'status' => 'Hadir', 'jarak' => 67, 'verifikasi' => 'Disetujui', 'lat' => 3.5950, 'lng' => 98.6721],
                ['id' => 27, 'tgl' => '2026-10-13', 'guruId' => 'G09', 'jam' => '06:58', 'status' => 'Hadir', 'jarak' => 39, 'verifikasi' => 'Disetujui', 'lat' => 3.5954, 'lng' => 98.6719],
                ['id' => 28, 'tgl' => '2026-10-13', 'guruId' => 'G10', 'jam' => '07:18', 'status' => 'Terlambat', 'jarak' => 70, 'verifikasi' => 'Disetujui', 'lat' => 3.5953, 'lng' => 98.6720],
                ['id' => 29, 'tgl' => '2026-10-13', 'guruId' => 'G11', 'jam' => '06:41', 'status' => 'Mencurigakan', 'jarak' => 410, 'verifikasi' => 'Ditolak', 'lat' => 3.5910, 'lng' => 98.6770],
                ['id' => 30, 'tgl' => '2026-10-13', 'guruId' => 'G12', 'jam' => '06:47', 'status' => 'Hadir', 'jarak' => 58, 'verifikasi' => 'Disetujui', 'lat' => 3.5951, 'lng' => 98.6723],
            ],

            'nilai' => [],
            'absensi_siswa' => [],
        ];
    }

    public static function arsipkanGuru(string $id): bool
    {
        $guru = self::guru();
        $ditemukan = false;
        foreach ($guru as &$profil) {
            if (($profil['id'] ?? null) === $id) {
                $profil['status'] = 'Nonaktif';
                $ditemukan = true;
                break;
            }
        }
        unset($profil);

        if (! $ditemukan) {
            return false;
        }

        self::put('guru', $guru);

        $akun = self::akun();
        foreach ($akun as &$account) {
            if (($account['guruId'] ?? null) === $id) {
                $account['aktif'] = false;
            }
        }
        unset($account);
        self::put('akun', $akun);

        return true;
    }

    public static function pengumuman(): array
    {
        return self::get('pengumuman');
    }

    /** Data siswa per kelas (statis). */
    public static function siswa(): array
    {
        return [
            'X-1' => ['Adi Nugroho', 'Bella Safitri', 'Citra Dewi', 'Dimas Prayoga', 'Eka Wijaya', 'Farhan Malik', 'Gita Purnama', 'Hafiz Rahman', 'Intan Permata', 'Joko Susilo', 'Kartika Sari', 'Lukman Hakim'],
            'X-2' => ['Maya Lestari', 'Nanda Rizky', 'Oscar Fernando', 'Putri Aulia', 'Rizal Ramadhan', 'Sinta Amalia', 'Taufik Hidayat', 'Ulfa Nabila', 'Vino Bastian', 'Winda Kartika', 'Yusuf Maulana', 'Zahra Aini'],
            'XI IPA 1' => ['Andika Putra', 'Bunga Citra', 'Candra Kirana', 'Danu Wicaksono', 'Elsa Pertiwi', 'Fajar Sidik', 'Gilang Ramadhan', 'Hana Ardiani', 'Irfan Hakim', 'Jihan Maulida', 'Kevin Sanjaya', 'Lidya Pratiwi'],
            'XI IPS 1' => ['Aldi Saputra', 'Bella Ananda', 'Cindy Rahayu', 'Dendi Kurnia', 'Endah Puspita', 'Ferdi Ardiansyah', 'Gina Oktaviani', 'Hendi Susanto', 'Indah Permatasari', 'Jaelani Nugraha', 'Kirana Dewi'],
            'XII IPA 1' => ['Aditya Wirawan', 'Bella Anggraini', 'Cucu Suherman', 'Dina Herlina', 'Erik Setiawan', 'Fina Marlina', 'Guntur Saputro', 'Hesti Rahayu', 'Ivan Gunawan', 'Julia Perez', 'Krisna Murti', 'Lalu Muhammad'],
            'XII IPS 1' => ['Ahmad Dahlan', 'Bulan Saputri', 'Chandra Elisabeth', 'Dodi Firmansyah', 'Erna Wati', 'Faisal Abdillah', 'Gina Savitri', 'Hendri Gunawan', 'Indri Hapsari', 'Jamal Mirdad', 'Komang Wulan', 'Lia Anjani'],
        ];
    }

    public static function daftarKelas(): array
    {
        return array_keys(self::siswa());
    }

    public static function jamPelajaran(): array
    {
        return [
            ['ke' => 1, 'mulai' => '07:00', 'selesai' => '07:45'],
            ['ke' => 2, 'mulai' => '07:45', 'selesai' => '08:30'],
            ['ke' => 3, 'mulai' => '08:30', 'selesai' => '09:15'],
            ['ke' => 4, 'mulai' => '09:35', 'selesai' => '10:20'],
            ['ke' => 5, 'mulai' => '10:20', 'selesai' => '11:05'],
            ['ke' => 6, 'mulai' => '11:05', 'selesai' => '11:50'],
            ['ke' => 7, 'mulai' => '12:30', 'selesai' => '13:15'],
            ['ke' => 8, 'mulai' => '13:15', 'selesai' => '14:00'],
        ];
    }

    public static function laporanBulanan(): array
    {
        return [
            '2026-10' => [
                ['guruId' => 'G01', 'hadir' => 14, 'terlambat' => 1, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G02', 'hadir' => 15, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G03', 'hadir' => 13, 'terlambat' => 1, 'izin' => 1, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G04', 'hadir' => 13, 'terlambat' => 2, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G05', 'hadir' => 14, 'terlambat' => 1, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G06', 'hadir' => 15, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G07', 'hadir' => 13, 'terlambat' => 2, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G08', 'hadir' => 14, 'terlambat' => 0, 'izin' => 1, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G09', 'hadir' => 15, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G10', 'hadir' => 13, 'terlambat' => 1, 'izin' => 0, 'sakit' => 1, 'alpa' => 0],
                ['guruId' => 'G11', 'hadir' => 12, 'terlambat' => 1, 'izin' => 1, 'sakit' => 0, 'alpa' => 1],
                ['guruId' => 'G12', 'hadir' => 14, 'terlambat' => 1, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
            ],
            '2026-09' => [
                ['guruId' => 'G01', 'hadir' => 20, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G02', 'hadir' => 19, 'terlambat' => 1, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G03', 'hadir' => 18, 'terlambat' => 2, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G04', 'hadir' => 20, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G05', 'hadir' => 19, 'terlambat' => 1, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G06', 'hadir' => 20, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G07', 'hadir' => 18, 'terlambat' => 1, 'izin' => 1, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G08', 'hadir' => 20, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G09', 'hadir' => 19, 'terlambat' => 1, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G10', 'hadir' => 18, 'terlambat' => 1, 'izin' => 1, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G11', 'hadir' => 19, 'terlambat' => 1, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
                ['guruId' => 'G12', 'hadir' => 20, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0],
            ],
        ];
    }

    public static function nilaiContoh(): array
    {
        return [
            'X-1|Matematika' => [
                ['siswa' => 'Adi Nugroho', 'tugas' => 85, 'kuis' => 80, 'uh' => 78, 'uts' => 82, 'uas' => 88],
                ['siswa' => 'Bella Safitri', 'tugas' => 92, 'kuis' => 90, 'uh' => 88, 'uts' => 91, 'uas' => 94],
                ['siswa' => 'Citra Dewi', 'tugas' => 70, 'kuis' => 65, 'uh' => 72, 'uts' => 68, 'uas' => 71],
                ['siswa' => 'Dimas Prayoga', 'tugas' => 55, 'kuis' => 60, 'uh' => 58, 'uts' => 62, 'uas' => 57],
                ['siswa' => 'Eka Wijaya', 'tugas' => 78, 'kuis' => 82, 'uh' => 80, 'uts' => 79, 'uas' => 84],
                ['siswa' => 'Farhan Malik', 'tugas' => 88, 'kuis' => 85, 'uh' => 90, 'uts' => 87, 'uas' => 86],
                ['siswa' => 'Gita Purnama', 'tugas' => 63, 'kuis' => 68, 'uh' => 65, 'uts' => 70, 'uas' => 66],
                ['siswa' => 'Hafiz Rahman', 'tugas' => 75, 'kuis' => 72, 'uh' => 77, 'uts' => 74, 'uas' => 76],
                ['siswa' => 'Intan Permata', 'tugas' => 95, 'kuis' => 93, 'uh' => 92, 'uts' => 96, 'uas' => 94],
                ['siswa' => 'Joko Susilo', 'tugas' => 60, 'kuis' => 58, 'uh' => 62, 'uts' => 59, 'uas' => 61],
            ],
        ];
    }

    // ====================== Akses file JSON ======================

    protected static function file(string $key): string
    {
        return storage_path('app/data/'.$key.'.json');
    }

    public static function get(string $key)
    {
        $defaults = self::defaults();
        $fallback = $defaults[$key] ?? [];
        $file = self::file($key);

        if (! is_file($file)) {
            self::put($key, $fallback);

            return $fallback;
        }

        $decoded = json_decode((string) file_get_contents($file), true);

        return is_array($decoded) ? $decoded : $fallback;
    }

    public static function put(string $key, $value): void
    {
        $dir = storage_path('app/data');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $written = file_put_contents(
            self::file($key),
            json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            LOCK_EX
        );
        if ($written === false) {
            throw new \RuntimeException("Gagal menyimpan data {$key}.");
        }
    }

    // ====================== Helper domain ======================

    public static function pengaturan(): array
    {
        return array_merge(self::defaults()['pengaturan'], self::get('pengaturan'));
    }

    public static function simpanPengaturan(array $data): array
    {
        $p = array_merge(self::pengaturan(), array_intersect_key($data, self::defaults()['pengaturan']));
        self::put('pengaturan', $p);

        return $p;
    }

    public static function guru(): array
    {
        return self::get('guru');
    }

    public static function cariGuru(?string $id): ?array
    {
        foreach (self::guru() as $g) {
            if (($g['id'] ?? null) === $id) {
                return $g;
            }
        }

        return null;
    }

    public static function jadwal(): array
    {
        return self::get('jadwal');
    }

    public static function kelasDiajar(string $guruId): array
    {
        return array_values(array_unique(array_map(
            fn (array $jadwal) => $jadwal['kelas'],
            array_filter(
                self::jadwal(),
                fn (array $jadwal) => ($jadwal['idGuru'] ?? null) === $guruId
                    && isset($jadwal['kelas'])
                    && in_array($jadwal['kelas'], self::daftarKelas(), true)
            )
        )));
    }

    public static function absensiGuru(): array
    {
        return self::get('absensi_guru');
    }

    /**
     * Saring data absensi berdasarkan tanggal, bulan, tahun, status, dan kata kunci guru.
     */
    public static function filterAbsensi(
        array $semua,
        ?string $tanggal = null,
        ?string $bulan = null,
        ?string $tahun = null,
        string $cari = '',
        string $status = ''
    ): array {
        $cari = strtolower(trim($cari));
        $hasil = [];

        foreach ($semua as $a) {
            $tgl = (string) ($a['tgl'] ?? '');

            if ($tanggal && $tgl !== $tanggal) {
                continue;
            }
            if ($bulan && ! str_starts_with($tgl, $bulan)) {
                continue;
            }
            if ($tahun && substr($tgl, 0, 4) !== $tahun) {
                continue;
            }
            if ($status && ($a['status'] ?? null) !== $status) {
                continue;
            }
            if ($cari !== '') {
                $g = self::cariGuru($a['guruId'] ?? null) ?? [];
                $teks = strtolower(($g['nama'] ?? '').' '.($g['nip'] ?? ''));
                if (! str_contains($teks, $cari)) {
                    continue;
                }
            }

            $hasil[] = $a;
        }

        return $hasil;
    }

    public static function akun(): array
    {
        $akun = self::get('akun');
        $defaults = [];
        foreach (self::defaults()['akun'] as $default) {
            $defaults[$default['user']] = $default;
        }

        foreach ($akun as &$account) {
            $legacyDefaults = $defaults[$account['user'] ?? ''] ?? [];
            foreach (['email', 'foto'] as $field) {
                if (empty($account[$field]) && ! empty($legacyDefaults[$field])) {
                    $account[$field] = $legacyDefaults[$field];
                }
            }
        }
        unset($account);

        return $akun;
    }

    public static function cariAkun(string $user): ?array
    {
        foreach (self::akun() as $a) {
            if (
                strcasecmp((string) ($a['user'] ?? ''), $user) === 0
                || strcasecmp((string) ($a['email'] ?? ''), $user) === 0
            ) {
                return $a;
            }
        }

        return null;
    }

    public static function siswaKelas(string $kelas): array
    {
        return self::siswa()[$kelas] ?? [];
    }

    public static function presensiHariIni(string $guruId): ?array
    {
        $hariIni = date('Y-m-d');
        foreach (self::absensiGuru() as $a) {
            if (($a['guruId'] ?? null) === $guruId && ($a['tgl'] ?? null) === $hariIni) {
                return $a;
            }
        }

        return null;
    }

    public static function catatPresensi(string $guruId, array $data): array
    {
        $semua = self::absensiGuru();
        $maxId = 0;
        foreach ($semua as $a) {
            $maxId = max($maxId, (int) ($a['id'] ?? 0));
        }
        $record = array_merge([
            'id' => $maxId + 1,
            'tgl' => date('Y-m-d'),
            'guruId' => $guruId,
            'jam' => date('H:i'),
            'status' => 'Belum Hadir',
            'jarak' => null,
            'verifikasi' => 'Menunggu',
            'lat' => null,
            'lng' => null,
            'buktiFoto' => [],
        ], $data);
        $record['id'] = $maxId + 1;

        // Ganti bila guru sudah absen di tanggal yang sama
        $diganti = false;
        foreach ($semua as $i => $a) {
            if (($a['guruId'] ?? null) === $guruId && ($a['tgl'] ?? null) === $record['tgl']) {
                $record['id'] = $a['id'] ?? $record['id'];
                $record['buktiFoto'] = array_values(array_unique(array_merge(
                    is_array($a['buktiFoto'] ?? null) ? $a['buktiFoto'] : [],
                    is_array($record['buktiFoto'] ?? null) ? $record['buktiFoto'] : []
                )));
                $semua[$i] = $record;
                $diganti = true;
                break;
            }
        }
        if (! $diganti) {
            $semua[] = $record;
        }

        self::put('absensi_guru', $semua);

        return $record;
    }

    public static function nilai(string $kelas, string $mapel): array
    {
        $semua = self::get('nilai');
        $key = $kelas.'|'.$mapel;
        if (isset($semua[$key]) && is_array($semua[$key])) {
            return $semua[$key];
        }

        $contoh = self::nilaiContoh()[$key] ?? [];
        $hasil = [];
        foreach (self::siswaKelas($kelas) as $i => $nama) {
            $dariContoh = null;
            foreach ($contoh as $row) {
                if (($row['siswa'] ?? null) === $nama) {
                    $dariContoh = $row;
                    break;
                }
            }
            $hasil[] = $dariContoh ?: [
                'siswa' => $nama,
                'tugas' => 70 + ($i % 5) * 5,
                'kuis' => 72 + ($i % 4) * 5,
                'uh' => 75 + ($i % 3) * 6,
                'uts' => 68 + ($i % 6) * 5,
                'uas' => 70 + ($i % 5) * 6,
            ];
        }

        $semua[$key] = $hasil;
        self::put('nilai', $semua);

        return $hasil;
    }

    public static function simpanNilai(string $kelas, string $mapel, array $rows): void
    {
        $semua = self::get('nilai');
        $semua[$kelas.'|'.$mapel] = array_values($rows);
        self::put('nilai', $semua);
    }

    public static function absensiSiswa(string $kelas, ?string $tanggal = null): array
    {
        $semua = self::get('absensi_siswa');
        $dataKelas = $semua[$kelas] ?? [];
        if (! is_array($dataKelas)) {
            $dataKelas = [];
        }

        if ($tanggal !== null) {
            if (isset($dataKelas[$tanggal]) && is_array($dataKelas[$tanggal])) {
                return $dataKelas[$tanggal];
            }

            if ($tanggal === date('Y-m-d') && self::isAbsensiSiswaLegacy($dataKelas)) {
                return $dataKelas;
            }
        } elseif (self::isAbsensiSiswaLegacy($dataKelas)) {
            return $dataKelas;
        }

        $hasil = [];
        foreach (self::siswaKelas($kelas) as $nama) {
            $hasil[$nama] = 'Hadir';
        }

        return $hasil;
    }

    public static function simpanAbsensiSiswa(string $kelas, array $status, ?string $tanggal = null): void
    {
        $semua = self::get('absensi_siswa');
        $bersih = [];
        foreach (self::siswaKelas($kelas) as $nama) {
            $nilai = $status[$nama] ?? 'Hadir';
            if (! in_array($nilai, ['Hadir', 'Izin', 'Sakit', 'Alpa'], true)) {
                $nilai = 'Hadir';
            }
            $bersih[$nama] = $nilai;
        }

        $tanggal ??= date('Y-m-d');
        if (! isset($semua[$kelas]) || ! is_array($semua[$kelas])) {
            $semua[$kelas] = [];
        }
        $semua[$kelas][$tanggal] = $bersih;

        self::put('absensi_siswa', $semua);
    }

    private static function isAbsensiSiswaLegacy(array $data): bool
    {
        if ($data === []) {
            return false;
        }

        foreach ($data as $status) {
            if (! in_array($status, ['Hadir', 'Izin', 'Sakit', 'Alpa'], true)) {
                return false;
            }
        }

        return true;
    }
}
