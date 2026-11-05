@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('heading', 'Dashboard Admin')

@section('content')
    <div class="page-head">
        <div>
            <h2>Selamat Datang, Admin!</h2>
            <p>Ringkasan aktivitas sekolah hari ini</p>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif

    <div class="metric-grid">
        <div class="metric-card metric-biru">
            <div class="metric-icon"><x-icon name="users" /></div>
            <div class="metric-info">
                <div class="metric-value">{{ $totalGuru }}</div>
                <div class="metric-label">Total Guru</div>
                <div class="metric-note">Guru terdaftar</div>
            </div>
        </div>

        <div class="metric-card metric-hijau">
            <div class="metric-icon"><x-icon name="check" /></div>
            <div class="metric-info">
                <div class="metric-value">{{ $hadir }}</div>
                <div class="metric-label">Hadir</div>
                <div class="metric-note">Tepat waktu hari ini</div>
            </div>
        </div>

        <div class="metric-card metric-amber">
            <div class="metric-icon"><x-icon name="clock" /></div>
            <div class="metric-info">
                <div class="metric-value">{{ $terlambat }}</div>
                <div class="metric-label">Terlambat</div>
                <div class="metric-note">Perlu pemantauan</div>
            </div>
        </div>

        <div class="metric-card metric-merah">
            <div class="metric-icon"><x-icon name="alert" /></div>
            <div class="metric-info">
                <div class="metric-value">{{ $belum }}</div>
                <div class="metric-label">Belum Hadir</div>
                <div class="metric-note">Belum ada presensi</div>
            </div>
        </div>
    </div>

    <div class="dashboard-bottom">
        <div class="card">
            <div class="card-title-flex">
                <div>
                    <h3 class="card-title" style="margin-bottom:5px;">Distribusi Kehadiran</h3>
                    <p class="card-subtitle">Komposisi status presensi guru hari ini</p>
                </div>
                <span class="mini-chip chip-emerald">{{ array_sum($distribusi) }} catatan</span>
            </div>

            @php
                $totalDistribusi = array_sum($distribusi);
                $distribusiWarna = ['#16a34a', '#d97706', '#2563eb', '#0ea5e9', '#dc2626', '#94a3b8'];
            @endphp

            <div class="distribution-list" style="margin-top:16px;">
                @foreach (['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Mencurigakan', 'Belum Hadir'] as $label)
                    @php
                        $key = match($label) {
                            'Hadir' => 0,
                            'Terlambat' => 1,
                            'Izin' => 2,
                            'Sakit' => 3,
                            'Mencurigakan' => 4,
                            'Belum Hadir' => 5,
                        };
                        $pct = $totalDistribusi > 0 ? round(($distribusi[$key] / $totalDistribusi) * 100) : 0;
                    @endphp
                    <div class="distribution-row">
                        <div class="distribution-head">
                            <span class="distribution-label">
                                <span class="distribution-dot" style="background:{{ $distribusiWarna[$key] }};"></span>
                                {{ $label }}
                            </span>
                            <strong>{{ $distribusi[$key] }}</strong>
                        </div>
                        <div class="distribution-track">
                            <div class="distribution-fill" style="width:{{ $pct }}%; background:{{ $distribusiWarna[$key] }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card leaderboard-card">
            <div class="card-title-flex">
                <div>
                    <h3 class="card-title" style="margin-bottom:5px;">
                        <span class="title-dot dot-emerald"></span>Guru Paling Disiplin
                    </h3>
                    <p class="card-subtitle">Rekap {{ $periodeLeaderboard }} · selalu tepat waktu tanpa izin, sakit, atau terlambat</p>
                </div>
                <span class="mini-chip chip-emerald">{{ $disiplinGuru->count() }} guru</span>
            </div>

            <div class="leaderboard-list">
                @forelse ($disiplinGuru as $g)
                    <div class="leaderboard-item {{ $loop->first ? 'is-gold' : '' }}">
                        <span class="rank-badge rank-{{ $loop->iteration <= 3 ? $loop->iteration : 'default' }}">{{ $loop->iteration }}</span>
                        <img class="leaderboard-avatar"
                             src="{{ media_url($g['foto'] ?? '') ?: 'https://i.pravatar.cc/80?u=' . urlencode($g['nama']) }}"
                             alt="{{ $g['nama'] }}">
                        <div class="leaderboard-main">
                            <div class="leaderboard-name">{{ $g['nama'] }}</div>
                            <div class="leaderboard-meta">{{ $g['mapel'] }} · {{ $g['persen'] }}% tepat waktu</div>
                        </div>
                        <span class="leaderboard-score">{{ $g['hadir'] }} sesi</span>
                    </div>
                @empty
                    <div class="leaderboard-empty">
                        Belum ada guru dengan rekam jejak kehadiran sempurna.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="card leaderboard-card">
            <div class="card-title-flex">
                <div>
                    <h3 class="card-title" style="margin-bottom:5px;">
                        <span class="title-dot dot-rose"></span>Catatan Kehadiran Perlu Perhatian
                    </h3>
                    <p class="card-subtitle">Rekap {{ $periodeLeaderboard }} · keterlambatan atau ketidakhadiran terbanyak</p>
                </div>
                <span class="mini-chip chip-rose">{{ $perhatianGuru->count() }} guru</span>
            </div>

            <div class="leaderboard-list">
                @forelse ($perhatianGuru as $g)
                    @php
                        $masalah = collect([
                            $g['terlambat'] > 0 ? $g['terlambat'] . '× terlambat' : null,
                            $g['izin'] > 0 ? $g['izin'] . '× izin' : null,
                            $g['sakit'] > 0 ? $g['sakit'] . '× sakit' : null,
                            $g['mencurigakan'] > 0 ? $g['mencurigakan'] . '× mencurigakan' : null,
                            $g['alpa'] > 0 ? $g['alpa'] . '× alpa' : null,
                            $g['belum'] > 0 ? $g['belum'] . '× belum hadir' : null,
                        ])->filter()->implode(' · ');
                    @endphp
                    <div class="leaderboard-item">
                        <span class="rank-badge rank-{{ $loop->iteration <= 3 ? $loop->iteration : 'default' }}">{{ $loop->iteration }}</span>
                        <img class="leaderboard-avatar"
                             src="{{ media_url($g['foto'] ?? '') ?: 'https://i.pravatar.cc/80?u=' . urlencode($g['nama']) }}"
                             alt="{{ $g['nama'] }}">
                        <div class="leaderboard-main">
                            <div class="leaderboard-name">{{ $g['nama'] }}</div>
                            <div class="leaderboard-meta" title="{{ $masalah }}">{{ $masalah }}</div>
                        </div>
                        <span class="leaderboard-score warn">{{ $g['masalah'] }} catatan</span>
                    </div>
                @empty
                    <div class="leaderboard-empty">
                        Tidak ada catatan kehadiran yang perlu diperhatikan.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <div class="card-title-flex">
            <h3 class="card-title">Presensi Terbaru</h3>
            <span style="font-size:12px; color:var(--teks-2);">{{ $tanggal }}</span>
        </div>

        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr>
                    <th>Guru</th>
                    <th>Jam</th>
                    <th>Status</th>
                    <th>Bukti Foto</th>
                    <th>Verifikasi</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($recent as $item)
                    @php
                        $g = App\Support\Sekolah::cariGuru($item['guruId'] ?? null) ?? [];
                        $bukti = bukti_foto_list($item);
                    @endphp
                    <tr>
                        <td>{{ $g['nama'] ?? '-' }}</td>
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
                    <tr><td colspan="6" style="text-align:center; color:var(--teks-2);">Belum ada data presensi</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
