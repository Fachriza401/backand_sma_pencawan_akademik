@extends('layouts.admin')

@section('title', 'Pemulihan Akun')
@section('heading', 'Pemulihan Akun')

@section('content')
    <div class="page-head recovery-page-head">
        <div>
            <h2>Permintaan Pemulihan Akun</h2>
            <p>Verifikasi identitas pemohon atau bantu guru mereset password secara langsung.</p>
        </div>
        <button type="button" class="btn btn-primary" data-modal-open="manual-password-reset-modal">
            <span aria-hidden="true">＋</span> Reset Password Manual
        </button>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-merah" role="alert" style="margin-bottom:16px;">{{ $errors->first() }}</div>
    @endif
    @if (session('recoveryCode'))
        <section role="status" class="card" style="margin-bottom:16px; border:1px solid #fbbf24; background:#fffbeb;">
            <h3 class="card-title">Kode pemulihan dibuat — tampil satu kali</h3>
            <p style="margin:0 0 10px; color:#78350f;">Sampaikan kode ini secara manual hanya melalui kontak pemohon yang sudah diverifikasi. Kode berlaku 30 menit dan tidak dapat dilihat kembali setelah meninggalkan halaman.</p>
            <p style="margin:0;">Referensi: <code>{{ session('recoveryCode.reference') }}</code></p>
            <p style="margin:8px 0 0; font-size:20px; font-weight:800; letter-spacing:3px; color:#1e3a8a;"><code>{{ session('recoveryCode.code') }}</code></p>
        </section>
    @endif

    <section class="card recovery-list-card">
        <nav class="recovery-tabs" aria-label="Filter permintaan pemulihan">
            <a href="{{ route('admin.pemulihan-akun', ['tab' => 'pending']) }}" class="{{ $tab === 'pending' ? 'active' : '' }}" @if ($tab === 'pending') aria-current="page" @endif>
                Permintaan Baru <span>{{ $jumlahPending }}</span>
            </a>
            <a href="{{ route('admin.pemulihan-akun', ['tab' => 'history']) }}" class="{{ $tab === 'history' ? 'active' : '' }}" @if ($tab === 'history') aria-current="page" @endif>
                Riwayat Selesai <span>{{ $jumlahRiwayat }}</span>
            </a>
        </nav>

        <div class="table-wrap">
            <table class="data recovery-table">
                <thead>
                <tr>
                    <th>Diajukan</th>
                    <th>Akun</th>
                    <th>Data verifikasi</th>
                    <th>Status</th>
                    <th>Tindakan</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($requests as $item)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($item['createdAt'])->format('d/m/Y H:i') }}</td>
                        <td>
                            <strong>{{ $item['identifierValue'] }}</strong>
                            <small class="recovery-account-meta">Ref: {{ $item['id'] }}</small>
                            @if ($item['registeredEmail'])
                                <small class="recovery-account-meta">Email akun: {{ $item['registeredEmail'] }}</small>
                            @endif
                        </td>
                        <td class="recovery-verification">{{ $item['verificationValue'] }}</td>
                        <td>
                            {!! badge_status(match($item['status']) { 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'completed' => 'Selesai', default => 'Kedaluwarsa' }) !!}
                        </td>
                        <td>
                            @if ($item['status'] === 'pending')
                                <div class="recovery-actions">
                                    <form method="POST" action="{{ route('admin.pemulihan-akun.approve', ['reference' => $item['id']]) }}">
                                        @csrf
                                        <button class="btn btn-primary btn-sm" type="submit">Verifikasi &amp; Setujui</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.pemulihan-akun.reject', ['reference' => $item['id']]) }}">
                                        @csrf
                                        <input name="reason" type="hidden" value="Data identitas belum dapat diverifikasi.">
                                        <button class="btn btn-outline btn-sm" type="submit">Tolak</button>
                                    </form>
                                </div>
                            @elseif ($item['status'] === 'approved')
                                <small>Kode sudah diterbitkan</small>
                            @else
                                <small>-</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="recovery-empty-state">
                                <span class="recovery-empty-icon" aria-hidden="true">
                                    <svg viewBox="0 0 48 48" fill="none">
                                        <path d="M24 5 39 11v11c0 10-6.4 17.4-15 21-8.6-3.6-15-11-15-21V11l15-6Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
                                        <rect x="17" y="21" width="14" height="11" rx="2" stroke="currentColor" stroke-width="2.4"/>
                                        <path d="M20 21v-3a4 4 0 0 1 8 0v3m-4 5v2" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <strong>{{ $tab === 'pending' ? 'Belum ada permintaan pemulihan akun saat ini' : 'Belum ada riwayat permintaan pemulihan' }}</strong>
                                <span>{{ $tab === 'pending' ? 'Permintaan baru yang dikirim pengguna akan muncul di sini.' : 'Permintaan yang telah ditinjau akan ditampilkan di bagian ini.' }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="modal-backdrop" id="manual-password-reset-modal" aria-hidden="true">
        <section class="modal recovery-reset-modal" role="dialog" aria-modal="true" aria-labelledby="manual-password-reset-title">
            <div class="modal-head">
                <div>
                    <h3 id="manual-password-reset-title">Reset Password Manual</h3>
                    <p class="modal-subtitle">Gunakan saat guru hadir langsung dan identitasnya sudah diverifikasi.</p>
                </div>
                <button type="button" class="modal-close" data-modal-close="manual-password-reset-modal" aria-label="Tutup">×</button>
            </div>
            <form method="POST" action="{{ route('admin.pemulihan-akun.reset-manual') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="manual-reset-guru">Akun Guru</label>
                        <select class="form-select" id="manual-reset-guru" name="guruId" required>
                            <option value="">Pilih guru aktif</option>
                            @foreach ($guruDenganAkun as $guru)
                                <option value="{{ $guru['id'] }}" @selected(old('guruId') === $guru['id'])>{{ $guru['nama'] }}{{ $guru['email'] !== '' ? ' · '.$guru['email'] : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="manual-reset-password">Password Baru</label>
                        <input class="form-input" id="manual-reset-password" name="password" type="password" minlength="8" maxlength="120" autocomplete="new-password" required>
                        <small class="form-hint">Minimal 8 karakter. Sampaikan password kepada guru secara langsung.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="manual-reset-confirmation">Konfirmasi Password Baru</label>
                        <input class="form-input" id="manual-reset-confirmation" name="password_confirmation" type="password" minlength="8" maxlength="120" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-outline" data-modal-close="manual-password-reset-modal">Batal</button>
                    <button type="submit" class="btn btn-primary" {{ $guruDenganAkun->isEmpty() ? 'disabled' : '' }}>Simpan Password Baru</button>
                </div>
            </form>
        </section>
    </div>

    @if ($errors->has('guruId') || $errors->has('password'))
        <script>
            window.addEventListener('DOMContentLoaded', () => window.SigapModal.open('manual-password-reset-modal'));
        </script>
    @endif
@endsection
