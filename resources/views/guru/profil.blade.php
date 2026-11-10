@extends('layouts.guru')

@section('title', 'Profil Saya')

@section('content')
    <div class="page-head">
        <div>
            <h2>Profil Saya</h2>
            <p>Kelola informasi kontak dan keamanan akun guru Anda.</p>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-merah" role="alert" style="margin-bottom:16px;">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('guru.profil.update') }}" class="guru-profile-card admin-profile-card">
        @csrf
        <section>
            <div class="admin-profile-section-heading">
                <div>
                    <span class="guru-section-kicker">IDENTITAS AKUN</span>
                    <h3>Informasi Guru</h3>
                    <p>Perbarui nama dan kontak yang digunakan pada akun Anda.</p>
                </div>
            </div>

            <div class="admin-profile-fields">
                <div class="form-group">
                    <label class="form-label" for="guru-profile-name">Nama Lengkap</label>
                    <input id="guru-profile-name" name="nama" class="form-input" type="text" maxlength="120" value="{{ old('nama', $guru['nama'] ?? '') }}" autocomplete="name" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="guru-profile-email">Email</label>
                    <input id="guru-profile-email" name="email" class="form-input" type="email" maxlength="120" value="{{ old('email', $akun['email'] ?? '') }}" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="guru-profile-phone">Nomor Telepon</label>
                    <input id="guru-profile-phone" name="telepon" class="form-input" type="tel" maxlength="24" value="{{ old('telepon', $guru['telepon'] ?? '') }}" autocomplete="tel" placeholder="+62 812 3456 7890">
                </div>
                <div class="form-group">
                    <label class="form-label" for="guru-profile-nip">NIP</label>
                    <input id="guru-profile-nip" class="form-input" type="text" value="{{ $guru['nip'] ?? '-' }}" readonly>
                    <small class="admin-profile-help">Hubungi administrator jika data NIP perlu diperbarui.</small>
                </div>
                <div class="form-group admin-profile-field-full">
                    <label class="form-label" for="guru-profile-subject">Mata Pelajaran</label>
                    <input id="guru-profile-subject" class="form-input" type="text" value="{{ $guru['mapel'] ?? '-' }}" readonly>
                </div>
            </div>
        </section>

        <section id="keamanan" class="admin-profile-password-section">
            <div class="admin-profile-section-heading">
                <div>
                    <span class="guru-section-kicker">KEAMANAN AKUN</span>
                    <h3>Ubah Kata Sandi</h3>
                    <p>Kosongkan bagian kata sandi jika tidak ingin menggantinya.</p>
                </div>
            </div>
            <div class="admin-profile-fields">
                <div class="form-group">
                    <label class="form-label" for="guru-current-password">Kata Sandi Saat Ini</label>
                    <input id="guru-current-password" name="current_password" class="form-input" type="password" autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label class="form-label" for="guru-new-password">Kata Sandi Baru</label>
                    <input id="guru-new-password" name="password" class="form-input" type="password" minlength="8" maxlength="120" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="form-label" for="guru-password-confirmation">Konfirmasi Kata Sandi Baru</label>
                    <input id="guru-password-confirmation" name="password_confirmation" class="form-input" type="password" minlength="8" maxlength="120" autocomplete="new-password">
                </div>
            </div>
        </section>

        <div class="admin-profile-actions">
            <a href="{{ route('guru.dashboard') }}" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
@endsection
