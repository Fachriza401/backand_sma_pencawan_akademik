@extends('layouts.admin')

@section('title', 'Jadwal Kelas')
@section('heading', 'Jadwal Kelas')

@section('content')
    @php
        $jadwalKelas = array_values(array_filter($jadwal, fn ($slot) => ($slot['kelas'] ?? null) === $kelas));
        $jadwalHariIni = array_values(array_filter($jadwalKelas, fn ($slot) => ($slot['hari'] ?? null) === $hariTerpilih));
        $jumlahGuruHariIni = collect($jadwalHariIni)
            ->map(fn ($slot) => ($slot['status'] ?? 'Regular') === 'Digantikan'
                ? ($slot['idGuruPengganti'] ?? $slot['idGuru'] ?? '')
                : ($slot['idGuru'] ?? ''))
            ->filter()->unique()->count();
        $jumlahMapelHariIni = collect($jadwalHariIni)->pluck('mapel')->filter()->unique()->count();
        $jumlahJamKosong = max(0, count($jam) - count($jadwalHariIni));
    @endphp

    <div class="page-head schedule-page-head">
        <div>
            <h2>Jadwal Kelas</h2>
            <p>Susun agenda harian kelas, isi jam kosong, dan periksa guru serta ruang yang digunakan.</p>
        </div>
        <div class="guru-actions">
            <button type="button" class="btn btn-primary" data-add-jadwal data-default="{{ json_encode(['kelas' => $kelas, 'hari' => $hariTerpilih], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}">Tambah Pelajaran</button>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif

    <section class="schedule-hero class-schedule-hero">
        <div class="schedule-hero-copy">
            <span class="schedule-kicker">PERENCANAAN HARIAN KELAS</span>
            <h2>Kelas {{ $kelas }}</h2>
            <p>Agenda {{ $hariTerpilih }} · pilih kelas dan hari untuk melihat seluruh jam pelajaran.</p>
        </div>
        <form method="GET" action="{{ route('admin.jadwal-kelas') }}" class="schedule-picker">
            <label for="pilih-kelas">Pilih kelas</label>
            <select class="form-select" id="pilih-kelas" name="kelas" onchange="this.form.submit()">
                @foreach ($daftarKelas as $k)
                    <option value="{{ $k }}" @selected($kelas === $k)>{{ $k }}</option>
                @endforeach
            </select>
            <label for="pilih-hari">Pilih hari</label>
            <select class="form-select" id="pilih-hari" name="hari" onchange="this.form.submit()">
                @foreach ($hari as $h)
                    <option value="{{ $h }}" @selected($hariTerpilih === $h)>{{ $h }}</option>
                @endforeach
            </select>
        </form>
        <div class="schedule-hero-stats">
            <div><strong>{{ count($jadwalHariIni) }}</strong><span>Pelajaran terjadwal</span></div>
            <div><strong>{{ $jumlahJamKosong }}</strong><span>Jam belum terisi</span></div>
            <div><strong>{{ $jumlahMapelHariIni }}</strong><span>Mata pelajaran hari ini</span></div>
        </div>
        <span class="schedule-hero-mark" aria-hidden="true">K</span>
    </section>

    <div class="card schedule-board class-schedule-board class-day-planner">
        <div class="schedule-board-heading">
            <div>
                <h3 class="card-title">Agenda Kelas · {{ $hariTerpilih }}</h3>
                <p class="card-subtitle">Setiap jam ditampilkan, termasuk jam kosong yang bisa langsung diisi.</p>
            </div>
            <span class="schedule-board-tag">{{ count($jadwalHariIni) }} dari {{ count($jam) }} jam terisi</span>
        </div>
        <div class="class-day-summary">
            <span><strong>{{ $jumlahGuruHariIni }}</strong> guru terlibat hari ini</span>
            <span>Jam pelajaran kosong dapat diisi tanpa berpindah menu.</span>
        </div>
        <div class="class-day-agenda">
            @foreach ($jam as $jp)
                @php
                    $idx = collect($jadwal)->search(fn ($row) => ($row['kelas'] ?? null) === $kelas && ($row['hari'] ?? null) === $hariTerpilih && ((int) ($row['jamKe'] ?? 0)) === (int) $jp['ke']);
                    $slot = $idx !== false ? ($jadwal[$idx] ?? null) : null;
                @endphp
                <div class="class-period-row {{ $slot ? 'is-filled' : 'is-empty' }}">
                    <div class="class-period-time">
                        <strong>Jam {{ $jp['ke'] }}</strong>
                        <span>{{ $jp['mulai'] }} – {{ $jp['selesai'] }}</span>
                    </div>
                    @if ($slot)
                        @php
                            $status = $slot['status'] ?? 'Regular';
                            $penggantiId = $slot['idGuruPengganti'] ?? '';
                            $namaGuru = $petaGuru[$slot['idGuru'] ?? ''] ?? ($slot['idGuru'] ?? '-');
                            $namaPengganti = $petaGuru[$penggantiId] ?? $penggantiId;
                            $payload = [
                                'idx' => $idx,
                                'hari' => $slot['hari'] ?? $hariTerpilih,
                                'jamKe' => (int) ($slot['jamKe'] ?? $jp['ke']),
                                'kelas' => $slot['kelas'] ?? $kelas,
                                'ruang' => $slot['ruang'] ?? '',
                                'mapel' => $slot['mapel'] ?? '',
                                'idGuru' => $slot['idGuru'] ?? '',
                                'status' => $status,
                                'idGuruPengganti' => $penggantiId,
                                'catatan' => $slot['catatan'] ?? '',
                            ];
                        @endphp
                        <button
                            type="button"
                            class="class-period-card {{ $status === 'Digantikan' ? 'is-substitute' : ($status === 'Kosong' ? 'is-no-teacher' : '') }}"
                            data-jadwal-slot
                            data-jadwal="{{ json_encode($payload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                        >
                            <span class="class-period-main">
                                <strong>{{ $slot['mapel'] ?? 'Mata pelajaran belum diisi' }}</strong>
                                <span>
                                    @if ($status === 'Digantikan')
                                        {{ $namaGuru }} · digantikan oleh {{ $namaPengganti ?: '-' }}
                                    @elseif ($status === 'Kosong')
                                        {{ $namaGuru }} · tidak hadir
                                    @else
                                        {{ $namaGuru }}
                                    @endif
                                    · Ruang {{ $slot['ruang'] ?? '-' }}
                                </span>
                            </span>
                            <span class="class-period-status">{!! badge_status($status) !!}<small>Edit</small></span>
                        </button>
                    @else
                        <div class="class-period-empty">
                            <span><strong>Jam kosong</strong><small>Belum ada pelajaran atau guru ditugaskan.</small></span>
                            <button
                                type="button"
                                class="btn btn-outline btn-sm"
                                data-add-jadwal
                                data-default="{{ json_encode(['hari' => $hariTerpilih, 'jamKe' => (int) $jp['ke'], 'kelas' => $kelas], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                            >Atur pelajaran</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    @include('admin.partials.jadwal-modal', [
        'simpanRoute' => route('admin.jadwal-kelas.simpan'),
        'hapusRoute' => route('admin.jadwal-kelas.hapus'),
    ])
@endsection
