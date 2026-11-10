<?php

use App\Http\Controllers\Admin\AbsensiController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Admin\InformasiController;
use App\Http\Controllers\Admin\JadwalController as AdminJadwalController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\ProfilController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Guru\AbsensiSiswaController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\JadwalController as GuruJadwalController;
use App\Http\Controllers\Guru\NilaiController;
use App\Http\Controllers\Guru\PresensiController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordRecoveryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin']);

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::get('/lupa-password', [PasswordRecoveryController::class, 'create'])->name('password.recovery.create');
Route::post('/lupa-password', [PasswordRecoveryController::class, 'store'])->middleware('throttle:5,15')->name('password.recovery.store');
Route::get('/lupa-password/status/{reference}', [PasswordRecoveryController::class, 'status'])->whereAlphaNumeric('reference')->name('password.recovery.status');
Route::get('/reset-password/{reference}', [PasswordRecoveryController::class, 'resetForm'])->whereAlphaNumeric('reference')->name('password.recovery.reset');
Route::post('/reset-password/{reference}', [PasswordRecoveryController::class, 'reset'])->whereAlphaNumeric('reference')->middleware('throttle:5,15')->name('password.recovery.update');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil');
    Route::post('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::get('/pemulihan-akun', [PasswordRecoveryController::class, 'adminIndex'])->name('pemulihan-akun');
    Route::post('/pemulihan-akun/{reference}/setujui', [PasswordRecoveryController::class, 'approve'])->whereAlphaNumeric('reference')->name('pemulihan-akun.approve');
    Route::post('/pemulihan-akun/{reference}/tolak', [PasswordRecoveryController::class, 'reject'])->whereAlphaNumeric('reference')->name('pemulihan-akun.reject');
    Route::post('/pemulihan-akun/reset-manual', [PasswordRecoveryController::class, 'manualReset'])->name('pemulihan-akun.reset-manual');
    Route::get('/informasi', [InformasiController::class, 'index'])->name('informasi');
    Route::post('/informasi/simpan', [InformasiController::class, 'simpan'])->name('informasi.simpan');
    Route::post('/informasi/hapus', [InformasiController::class, 'hapus'])->name('informasi.hapus');
    Route::get('/notifikasi', [NotificationController::class, 'admin'])->name('notifikasi');
    Route::post('/fitur-guru', [AdminDashboardController::class, 'toggleFeature'])->name('fitur-guru');
    Route::get('/guru', [GuruController::class, 'index'])->name('guru');
    Route::post('/guru/simpan', [GuruController::class, 'simpan'])->name('guru.simpan');
    Route::post('/guru/impor', [GuruController::class, 'impor'])->name('guru.impor');
    Route::get('/guru/template', [GuruController::class, 'template'])->name('guru.template');
    Route::post('/guru/hapus', [GuruController::class, 'hapus'])->name('guru.hapus');
    Route::post('/guru/nonaktif', [GuruController::class, 'nonaktif'])->name('guru.nonaktif');

    Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa');
    Route::post('/siswa/simpan', [SiswaController::class, 'simpan'])->name('siswa.simpan');
    Route::post('/siswa/hapus', [SiswaController::class, 'hapus'])->name('siswa.hapus');

    Route::get('/jadwal-guru', [AdminJadwalController::class, 'jadwalGuru'])->name('jadwal-guru');
    Route::get('/jadwal-kelas', [AdminJadwalController::class, 'kelas'])->name('jadwal-kelas');
    Route::post('/jadwal-guru/simpan', [AdminJadwalController::class, 'simpanGuru'])->name('jadwal-guru.simpan');
    Route::post('/jadwal-guru/hapus', [AdminJadwalController::class, 'hapusGuru'])->name('jadwal-guru.hapus');
    Route::post('/jadwal-kelas/simpan', [AdminJadwalController::class, 'simpanKelas'])->name('jadwal-kelas.simpan');
    Route::post('/jadwal-kelas/hapus', [AdminJadwalController::class, 'hapusKelas'])->name('jadwal-kelas.hapus');

    Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi');
    Route::post('/absensi/status', [AbsensiController::class, 'status'])->name('absensi.status');
    Route::post('/absensi/tambah-manual', [AbsensiController::class, 'tambahManual'])->name('absensi.tambah-manual');
    Route::post('/absensi/rapikan-status-lama', [AbsensiController::class, 'rapikanStatusLama'])->name('absensi.rapikan-status-lama');
    Route::post('/absensi/hapus', [AbsensiController::class, 'hapus'])->name('absensi.hapus');
    Route::get('/absensi/ekspor', [AbsensiController::class, 'ekspor'])->name('absensi.ekspor');

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');
    Route::get('/laporan/ekspor', [LaporanController::class, 'ekspor'])->name('laporan.ekspor');
    Route::post('/laporan/catatan', [LaporanController::class, 'simpanCatatan'])->name('laporan.catatan');
});

Route::middleware(['role:guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifikasi', [NotificationController::class, 'guru'])->name('notifikasi');
    Route::get('/jadwal', [GuruJadwalController::class, 'index'])->name('jadwal');
    Route::middleware('teacher-feature')->group(function () {
        Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi');
        Route::post('/presensi', [PresensiController::class, 'store'])->name('presensi.store');
    });
    Route::get('/absensi-siswa', [AbsensiSiswaController::class, 'index'])->name('absensi-siswa');
    Route::post('/absensi-siswa', [AbsensiSiswaController::class, 'store'])->name('absensi-siswa.store');
    Route::post('/absensi-siswa/siswa', [AbsensiSiswaController::class, 'simpanSiswa'])->name('absensi-siswa.siswa.simpan');
    Route::delete('/absensi-siswa/siswa', [AbsensiSiswaController::class, 'hapusSiswa'])->name('absensi-siswa.siswa.hapus');
    Route::get('/nilai', [NilaiController::class, 'index'])->name('nilai');
    Route::post('/nilai', [NilaiController::class, 'store'])->name('nilai.store');
    Route::post('/nilai/impor', [NilaiController::class, 'impor'])->name('nilai.impor');
    Route::get('/nilai/ekspor', [NilaiController::class, 'ekspor'])->name('nilai.ekspor');
    Route::get('/rapor', [NilaiController::class, 'rapor'])->name('rapor');
    Route::get('/rapor/ekspor', [NilaiController::class, 'eksporRapor'])->name('rapor.ekspor');
});
