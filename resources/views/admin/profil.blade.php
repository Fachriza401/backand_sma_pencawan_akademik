@extends('layouts.admin')

@section('title', 'Profil Admin')
@section('heading', 'Profil Admin')

@section('content')
    <div class="page-head">
        <div>
            <h2>Profil Admin</h2>
            <p>Perbarui informasi akun administrator.</p>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-merah" role="alert" style="margin-bottom:16px;">{{ $errors->first() }}</div>
    @endif

    <div class="card" style="max-width:760px;">
        <form method="POST" action="{{ route('admin.profil.update') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="nama">Nama Admin</label>
                <input id="nama" name="nama" class="form-input" type="text" maxlength="120" value="{{ old('nama', $akun['nama'] ?? '') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input id="email" name="email" class="form-input" type="email" maxlength="120" value="{{ old('email', $akun['email'] ?? '') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="teleponAdmin">Nomor WhatsApp Admin (opsional)</label>
                <input id="teleponAdmin" name="teleponAdmin" class="form-input" type="tel" maxlength="24" autocomplete="tel" placeholder="+62 812 3456 7890" value="{{ old('teleponAdmin', $pengaturan['teleponAdmin'] ?? '') }}">
                <small style="display:block; margin-top:5px; color:var(--teks-2);">Nomor ini ditampilkan sebagai opsi kontak di halaman login. Gunakan nomor aktif dengan kode negara.</small>
            </div>

            <hr style="border:0; border-top:1px solid var(--garis); margin:22px 0;">
            <h3 class="card-title">Ganti Password</h3>
            <p style="color:var(--teks-2); margin:0 0 16px;">Kosongkan bagian password jika tidak ingin menggantinya. Password saat ini diperlukan untuk menyimpan password baru.</p>
            <div class="form-group">
                <label class="form-label" for="current_password">Password Saat Ini</label>
                <input id="current_password" name="current_password" class="form-input" type="password" autocomplete="current-password">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password Baru</label>
                    <input id="password" name="password" class="form-input" type="password" minlength="8" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Konfirmasi Password Baru</label>
                    <input id="password_confirmation" name="password_confirmation" class="form-input" type="password" minlength="8" autocomplete="new-password">
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:8px;">
                <button type="submit" class="btn btn-primary">Simpan Profil</button>
            </div>
        </form>
    </div>
@endsection
