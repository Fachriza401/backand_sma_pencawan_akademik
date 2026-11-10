@extends('layouts.admin')

@section('title', 'Profil Admin')
@section('heading', 'Profil Admin')

@section('content')
    <div class="page-head">
        <div>
            <h2>Profil Admin</h2>
            <p>Kelola identitas, keamanan akun, dan informasi akses administrator.</p>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-merah" role="alert" style="margin-bottom:16px;">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.profil.update') }}" enctype="multipart/form-data" class="admin-profile-layout" x-data="adminProfile(@js($fotoProfil))" x-cloak>
        @csrf
        <div class="admin-profile-main">
            <section class="card admin-profile-card">
                <div class="admin-profile-section-heading">
                    <div>
                        <span class="dashboard-panel-kicker">IDENTITAS AKUN</span>
                        <h3>Profil Administrator</h3>
                        <p>Foto dan informasi yang digunakan pada akun admin.</p>
                    </div>
                </div>

                <div class="admin-profile-avatar-card">
                    <div class="admin-profile-avatar-wrap">
                        <img x-show="photoPreview" :src="photoPreview" alt="Foto profil Admin">
                        <span x-show="!photoPreview" class="admin-profile-avatar-fallback">A</span>
                    </div>
                    <div class="admin-profile-avatar-copy">
                        <strong>Foto Profil</strong>
                        <span>Gunakan foto JPG atau PNG, ukuran maksimal 2 MB.</span>
                        <label for="foto" class="btn btn-outline btn-sm">Unggah Foto Baru</label>
                        <input id="foto" name="foto" class="admin-profile-file-input" type="file" accept="image/jpeg,image/png,.jpg,.jpeg,.png" @change="previewPhoto($event)">
                        @error('foto')<small class="admin-profile-error">{{ $message }}</small>@enderror
                    </div>
                    <span class="admin-role-badge"><x-icon name="check" /> Full Administrator <small>Super Admin</small></span>
                </div>

                <div class="admin-profile-fields">
                    <div class="form-group">
                        <label class="form-label" for="nama">Nama Admin</label>
                        <input id="nama" name="nama" class="form-input" type="text" value="Admin" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" name="email" class="form-input" type="email" maxlength="120" value="{{ old('email', $akun['email'] ?? '') }}" required autocomplete="email">
                    </div>
                    <div class="form-group admin-profile-field-full">
                        <label class="form-label" for="teleponAdmin">Nomor WhatsApp Admin (opsional)</label>
                        <input id="teleponAdmin" name="teleponAdmin" class="form-input" type="tel" maxlength="24" autocomplete="tel" placeholder="+62 812 3456 7890" value="{{ old('teleponAdmin', $pengaturan['teleponAdmin'] ?? '') }}">
                        <small class="admin-profile-help">Nomor ini ditampilkan sebagai opsi kontak di halaman login.</small>
                    </div>
                </div>

                <div class="admin-profile-password-section">
                    <div class="admin-profile-section-heading">
                        <div>
                            <span class="dashboard-panel-kicker">KEAMANAN AKUN</span>
                            <h3>Ganti Password</h3>
                            <p>Kosongkan password baru jika tidak ingin menggantinya.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="current_password">Password Saat Ini</label>
                        <div class="admin-password-field">
                            <input id="current_password" name="current_password" class="form-input" :type="visiblePasswords.current ? 'text' : 'password'" autocomplete="current-password">
                            <button type="button" class="admin-password-toggle" @click="togglePassword('current')" :aria-label="visiblePasswords.current ? 'Sembunyikan password saat ini' : 'Tampilkan password saat ini'">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="admin-profile-fields">
                        <div class="form-group">
                            <label class="form-label" for="password">Password Baru</label>
                            <div class="admin-password-field">
                                <input id="password" name="password" class="form-input" :type="visiblePasswords.new ? 'text' : 'password'" x-model="password" minlength="8" autocomplete="new-password" aria-describedby="password-strength-label">
                                <button type="button" class="admin-password-toggle" @click="togglePassword('new')" :aria-label="visiblePasswords.new ? 'Sembunyikan password baru' : 'Tampilkan password baru'">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <div class="admin-password-strength" aria-live="polite">
                                <div class="admin-password-strength-bars" aria-hidden="true">
                                    <i :data-level="passwordStrength.bars >= 1 ? passwordStrength.level : ''"></i>
                                    <i :data-level="passwordStrength.bars >= 2 ? passwordStrength.level : ''"></i>
                                    <i :data-level="passwordStrength.bars >= 3 ? passwordStrength.level : ''"></i>
                                </div>
                                <span id="password-strength-label">Kekuatan password: <span x-text="passwordStrength.label">belum diisi</span></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="password_confirmation">Konfirmasi Password Baru</label>
                            <div class="admin-password-field">
                                <input id="password_confirmation" name="password_confirmation" class="form-input" :type="visiblePasswords.confirmation ? 'text' : 'password'" minlength="8" autocomplete="new-password">
                                <button type="button" class="admin-password-toggle" @click="togglePassword('confirmation')" :aria-label="visiblePasswords.confirmation ? 'Sembunyikan konfirmasi password' : 'Tampilkan konfirmasi password'">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="admin-profile-actions">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Profil</button>
                </div>
            </section>
        </div>

        <aside class="admin-profile-aside">
            <section class="card admin-access-card">
                <div class="admin-profile-section-heading">
                    <div>
                        <span class="dashboard-panel-kicker">OTORISASI</span>
                        <h3>Hak Akses</h3>
                    </div>
                </div>
                <div class="admin-access-badge"><x-icon name="check" /><span><strong>Full Administrator</strong><small>Super Admin</small></span></div>
                <p>Akun ini memiliki akses penuh ke seluruh modul SIAKAD SMA Pencawan.</p>
                <ul>
                    <li>Data guru dan siswa</li>
                    <li>Jadwal kelas dan guru</li>
                    <li>Presensi dan laporan</li>
                    <li>Pengaturan sistem</li>
                </ul>
            </section>

            <section class="card admin-login-history-card">
                <div class="admin-profile-section-heading">
                    <div>
                        <span class="dashboard-panel-kicker">KEAMANAN</span>
                        <h3>Riwayat Login Terakhir</h3>
                        <p>Informasi autentikasi akun admin.</p>
                    </div>
                    <span class="admin-history-icon"><x-icon name="clock" /></span>
                </div>
                @if (is_array($riwayatLogin) && ! empty($riwayatLogin['at']))
                    <dl class="admin-login-details">
                        <div><dt>Waktu login</dt><dd>{{ \Illuminate\Support\Carbon::parse($riwayatLogin['at'])->locale('id')->translatedFormat('d M Y, H:i') }} WIB</dd></div>
                        <div><dt>Alamat IP</dt><dd>{{ $riwayatLogin['ip'] ?? '-' }}</dd></div>
                        <div><dt>Perangkat</dt><dd>{{ $riwayatLogin['device'] ?? 'Perangkat tidak diketahui' }}</dd></div>
                    </dl>
                @else
                    <p class="admin-login-empty">Belum ada riwayat login yang tersimpan untuk akun ini.</p>
                @endif
            </section>
        </aside>
    </form>

@endsection
