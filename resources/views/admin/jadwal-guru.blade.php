@extends('layouts.admin')

@section('title', 'Jadwal Guru')
@section('heading', 'Jadwal Guru')

@section('content')
    <div class="page-head">
        <div>
            <h2>Jadwal Guru</h2>
            <p>Klik slot untuk mengedit, menandai guru pengganti, atau mengosongkan jam pelajaran.</p>
        </div>
        <div class="guru-actions">
            <button type="button" class="btn btn-primary" data-add-jadwal data-default='{"idGuru":"{{ $idGuru }}"}'>Tambah Jadwal</button>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="toolbar">
            <label class="form-label" style="margin:0;">Pilih Guru</label>
            <select class="form-select" onchange="location='{{ route('admin.jadwal-guru') }}?guru='+this.value">
                @foreach ($daftarGuru as $guru)
                    <option value="{{ $guru['id'] ?? '' }}" {{ ($idGuru === ($guru['id'] ?? '')) ? 'selected' : '' }}>{{ $guru['nama'] ?? '-' }}</option>
                @endforeach
            </select>
            <span style="margin-left:auto; color:var(--teks-2); font-size:12.5px;">{{ count(array_filter($jadwal, fn ($s) => ($s['idGuru'] ?? null) === $idGuru)) }} slot terjadwal</span>
        </div>

        <div class="jadwal-legend">
            <span><i class="lg-regular"></i> Regular</span>
            <span><i class="lg-diganti"></i> Digantikan</span>
            <span><i class="lg-kosong"></i> Kosong / tanpa guru</span>
        </div>

        <div class="jadwal-grid-wrap">
            <table class="jadwal-grid">
                <thead>
                <tr>
                    <th class="jam-col">Jam</th>
                    @foreach ($hari as $h)
                        <th>{{ $h }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($jam as $jp)
                    <tr>
                        <td class="jam-col">{{ $jp['ke'] }}<br><small>{{ $jp['mulai'] }} - {{ $jp['selesai'] }}</small></td>
                        @foreach ($hari as $h)
                            @php
                                $idx = collect($jadwal)->search(function ($row) use ($idGuru, $h, $jp) {
                                    return ($row['idGuru'] ?? null) === $idGuru && ($row['hari'] ?? null) === $h && ((int) ($row['jamKe'] ?? 0)) === (int) $jp['ke'];
                                });
                                $slot = $idx !== false ? ($jadwal[$idx] ?? null) : null;
                            @endphp
                            <td>
                                @if ($slot)
                                    @php
                                        $status = $slot['status'] ?? 'Regular';
                                        $penggantiId = $slot['idGuruPengganti'] ?? '';
                                        $namaPengganti = $petaGuru[$penggantiId] ?? $penggantiId;
                                        $kelasSlot = $status === 'Digantikan' ? 'digantikan' : ($status === 'Kosong' ? 'tidak-ada' : '');
                                        $payload = [
                                            'idx' => $idx,
                                            'hari' => $slot['hari'] ?? $h,
                                            'jamKe' => (int) ($slot['jamKe'] ?? $jp['ke']),
                                            'kelas' => $slot['kelas'] ?? '',
                                            'ruang' => $slot['ruang'] ?? '',
                                            'mapel' => $slot['mapel'] ?? '',
                                            'idGuru' => $slot['idGuru'] ?? $idGuru,
                                            'status' => $status,
                                            'idGuruPengganti' => $penggantiId,
                                            'catatan' => $slot['catatan'] ?? '',
                                        ];
                                    @endphp
                                    <button
                                        type="button"
                                        class="jadwal-slot isi {{ $kelasSlot }}"
                                        data-jadwal-slot
                                        data-jadwal="{{ json_encode($payload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                                    >
                                        <div class="slot-top">
                                            <span class="m">{{ $slot['mapel'] ?? '-' }}</span>
                                            <span class="slot-edit">Edit</span>
                                        </div>
                                        <div class="k">{{ $slot['kelas'] ?? '-' }}</div>
                                        @if ($status === 'Digantikan')
                                            <div class="k">Pengganti: {{ $namaPengganti ?: '-' }}</div>
                                        @elseif ($status === 'Kosong')
                                            <div class="k">Tidak ada guru</div>
                                        @else
                                            <div class="k">{{ $petaGuru[$slot['idGuru'] ?? ''] ?? ($slot['idGuru'] ?? '-') }}</div>
                                        @endif
                                        <div class="k">{{ $slot['ruang'] ?? '-' }}</div>
                                        <div style="margin-top:5px;">{!! badge_status($status) !!}</div>
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        class="jadwal-slot kosong"
                                        data-add-jadwal
                                        data-default="{{ json_encode(['hari' => $h, 'jamKe' => (int) $jp['ke'], 'idGuru' => $idGuru], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                                    >+ Tambah</button>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('admin.partials.jadwal-modal', [
        'simpanRoute' => route('admin.jadwal-guru.simpan'),
        'hapusRoute' => route('admin.jadwal-guru.hapus'),
    ])
@endsection
