@extends('layouts.guru')

@section('title', 'Presensi Guru')

@section('content')
    <div class="page-head">
        <div>
            <h2>Presensi Guru</h2>
            <p>Kirim kehadiran dan lihat riwayat presensi</p>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <h3 class="card-title">Form Presensi</h3>
        <form method="POST" action="{{ route('guru.presensi.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label" for="jarak">Jarak dari sekolah (meter)</label>
                <input id="jarak" name="jarak" class="form-input" type="number" min="0" step="0.1" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="lat">Latitude</label>
                    <input id="lat" name="lat" class="form-input" type="number" step="0.000001" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="lng">Longitude</label>
                    <input id="lng" name="lng" class="form-input" type="number" step="0.000001" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="buktiFoto">Bukti Foto (opsional)</label>
                <input id="buktiFoto" name="buktiFoto[]" class="form-input" type="file" accept="image/jpeg,image/png,image/webp" capture="environment" multiple>
                <small class="form-hint" style="display:block; margin-top:5px;">Lampirkan maksimal 3 foto. Format JPG, PNG, atau WEBP, maksimal 2 MB per foto.</small>
                @error('buktiFoto') <small class="form-error" style="display:block;">{{ $message }}</small> @enderror
                @error('buktiFoto.*') <small class="form-error" style="display:block;">{{ $message }}</small> @enderror
            </div>
            <button type="submit" class="btn btn-primary btn-block">Kirim Presensi</button>
        </form>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 class="card-title">Riwayat Presensi</h3>
        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Status</th>
                    <th>Bukti</th>
                    <th>Verifikasi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($riwayat as $item)
                    @php $bukti = bukti_foto_list($item); @endphp
                    <tr>
                        <td>{{ $item['tgl'] ?? '-' }}</td>
                        <td>{{ $item['jam'] ?? '-' }}</td>
                        <td>{!! badge_status($item['status'] ?? '-') !!}</td>
                        <td>
                            @if ($bukti !== [])
                                <div class="bukti-list">
                                    @foreach ($bukti as $foto)
                                        <a href="{{ media_url($foto) }}" target="_blank" rel="noopener" title="Lihat bukti foto">
                                            <img class="bukti-thumb" src="{{ media_url($foto) }}" alt="Bukti foto presensi">
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{!! badge_status($item['verifikasi'] ?? '-') !!}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center; color:var(--teks-2);">Belum ada riwayat presensi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
