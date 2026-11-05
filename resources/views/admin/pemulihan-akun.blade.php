@extends('layouts.admin')

@section('title', 'Pemulihan Akun')
@section('heading', 'Pemulihan Akun')

@section('content')
    <div class="page-head">
        <div>
            <h2>Permintaan Pemulihan Akun</h2>
            <p>Periksa identitas pemohon sebelum menyetujui reset password.</p>
        </div>
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

    <div class="card">
        <div class="table-wrap">
            <table class="data">
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
                            <small style="display:block; color:var(--teks-2); word-break:break-all;">Ref: {{ $item['id'] }}</small>
                            @if ($item['registeredEmail'])
                                <small style="display:block; color:var(--teks-2); word-break:break-all;">Email akun: {{ $item['registeredEmail'] }}</small>
                            @endif
                        </td>
                        <td style="word-break:break-word;">{{ $item['verificationValue'] }}</td>
                        <td>{!! badge_status(match($item['status']) { 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'completed' => 'Selesai', default => 'Kedaluwarsa' }) !!}</td>
                        <td>
                            @if ($item['status'] === 'pending')
                                <div style="display:grid; gap:8px; min-width:150px;">
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
                    <tr><td colspan="5" style="text-align:center; color:var(--teks-2);">Belum ada permintaan pemulihan akun.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
