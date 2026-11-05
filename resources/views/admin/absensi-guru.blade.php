@extends('layouts.admin')

@section('title', 'Absensi Guru')
@section('heading', 'Absensi Guru')

@section('content')
    <div class="page-head">
        <div>
            <h2>Absensi Guru</h2>
            <p>Kelola status kehadiran, bukti foto, verifikasi, dan ekspor data.</p>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-merah" role="alert" style="margin-bottom:16px;">{{ $errors->first() }}</div>
    @endif

    <div class="stat-grid">
        <div class="stat-card"><div class="stat-icon hijau"><x-icon name="check" /></div><div class="stat-info"><div class="angka">{{ $ringkasan['hadir'] }}</div><div class="label">Hadir</div></div></div>
        <div class="stat-card"><div class="stat-icon kuning"><x-icon name="clock" /></div><div class="stat-info"><div class="angka">{{ $ringkasan['terlambat'] }}</div><div class="label">Terlambat</div></div></div>
        <div class="stat-card"><div class="stat-icon biru"><x-icon name="clipboard" /></div><div class="stat-info"><div class="angka">{{ $ringkasan['izin'] }}</div><div class="label">Izin/Sakit</div></div></div>
        <div class="stat-card"><div class="stat-icon merah"><x-icon name="alert" /></div><div class="stat-info"><div class="angka">{{ $ringkasan['curiga'] }}</div><div class="label">Mencurigakan</div></div></div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('admin.absensi') }}" class="toolbar absensi-filter">
            <input class="form-input grow" type="search" name="cari" value="{{ $cari }}" placeholder="Cari nama atau NIP guru..." aria-label="Cari guru">
            <input class="form-input" type="date" name="tgl" value="{{ $filterTgl }}" aria-label="Filter tanggal"
                   onchange="if(this.value){this.form.bulan.value='';this.form.tahun.value='';}">
            <input class="form-input" type="month" name="bulan" value="{{ $filterBulan }}" aria-label="Filter bulan"
                   onchange="if(this.value){this.form.tgl.value='';this.form.tahun.value='';}">
            <input class="form-input year-input" type="number" name="tahun" min="2000" max="2100" value="{{ $filterTahun }}" placeholder="Tahun" aria-label="Filter tahun"
                   onchange="if(this.value){this.form.tgl.value='';this.form.bulan.value='';}">
            <select class="form-select" name="status" aria-label="Filter status" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach (\App\Support\Sekolah::STATUS_ABSENSI as $pilihan)
                    <option value="{{ $pilihan }}" @selected($status === $pilihan)>{{ $pilihan }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary btn-sm" type="submit">Terapkan</button>
            <a href="{{ route('admin.absensi') }}" class="btn btn-outline btn-sm">Reset</a>
            <a href="{{ route('admin.absensi.ekspor', array_filter([
                'tgl' => $filterTgl,
                'bulan' => $filterBulan,
                'tahun' => $filterTahun,
                'cari' => $cari,
                'status' => $status,
            ], fn ($nilai) => $nilai !== null && $nilai !== '')) }}" class="btn btn-hijau btn-sm">Ekspor XLSX</a>
        </form>

        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr>
                    <th>Guru</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Status</th>
                    <th>Bukti Foto</th>
                    <th>Verifikasi</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($data as $item)
                    @php
                        $g = App\Support\Sekolah::cariGuru($item['guruId'] ?? null) ?? [];
                        $bukti = bukti_foto_list($item);
                    @endphp
                    <tr data-absensi-id="{{ $item['id'] ?? '' }}">
                        <td>
                            <div class="table-person">
                                <img class="avatar avatar-sm" src="{{ media_url($g['foto'] ?? '') ?: 'https://i.pravatar.cc/80?u=' . urlencode($g['nama'] ?? 'guru') }}" alt="">
                                <span>{{ $g['nama'] ?? '-' }}</span>
                            </div>
                        </td>
                        <td>{{ $item['tgl'] ?? '-' }}</td>
                        <td>{{ $item['jam'] ?? '-' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.absensi.status') }}" class="inline-form">
                                @csrf
                                <input type="hidden" name="id" value="{{ $item['id'] ?? '' }}">
                                <select name="status" class="form-select status-inline" aria-label="Ubah status {{ $g['nama'] ?? 'guru' }}" onchange="this.form.submit()">
                                    @foreach (\App\Support\Sekolah::STATUS_ABSENSI as $pilihan)
                                        <option value="{{ $pilihan }}" @selected(($item['status'] ?? '') === $pilihan)>{{ $pilihan }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td>
                            @if ($bukti !== [])
                                <div class="bukti-list">
                                    @foreach ($bukti as $foto)
                                        <a href="{{ media_url($foto) }}" target="_blank" rel="noopener" title="Lihat bukti foto">
                                            <img class="bukti-thumb" src="{{ media_url($foto) }}" alt="Bukti foto {{ $g['nama'] ?? 'guru' }}">
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.absensi.verifikasi') }}" class="inline-form">
                                @csrf
                                <input type="hidden" name="id" value="{{ $item['id'] ?? '' }}">
                                <select name="status" class="form-select status-inline" aria-label="Verifikasi {{ $g['nama'] ?? 'guru' }}" onchange="this.form.submit()">
                                    <option value="" disabled @selected(! in_array($item['verifikasi'] ?? '', ['Disetujui', 'Ditolak'], true))>Pilih</option>
                                    <option value="Disetujui" @selected(($item['verifikasi'] ?? '') === 'Disetujui')>Disetujui</option>
                                    <option value="Ditolak" @selected(($item['verifikasi'] ?? '') === 'Ditolak')>Ditolak</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.absensi.hapus') }}" onsubmit="return confirm('Hapus data presensi ini? Tindakan ini tidak dapat dibatalkan.');">
                                @csrf
                                <input type="hidden" name="id" value="{{ $item['id'] ?? '' }}">
                                <button class="btn btn-merah btn-sm" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; color:var(--teks-2);">Tidak ada data presensi untuk filter yang dipilih.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($data->hasPages())
            <div class="pagination">
                <span style="margin-right:auto;">Menampilkan {{ $data->firstItem() }}-{{ $data->lastItem() }} dari {{ $data->total() }} data</span>
                @if ($data->onFirstPage())
                    <button type="button" disabled>‹</button>
                @else
                    <a href="{{ $data->previousPageUrl() }}"><button type="button">‹</button></a>
                @endif
                @foreach ($data->getUrlRange(max(1, $data->currentPage() - 2), min($data->lastPage(), $data->currentPage() + 2)) as $halaman => $url)
                    <a href="{{ $url }}"><button type="button" class="{{ $data->currentPage() === $halaman ? 'active' : '' }}">{{ $halaman }}</button></a>
                @endforeach
                @if ($data->hasMorePages())
                    <a href="{{ $data->nextPageUrl() }}"><button type="button">›</button></a>
                @else
                    <button type="button" disabled>›</button>
                @endif
            </div>
        @endif
    </div>
@endsection
