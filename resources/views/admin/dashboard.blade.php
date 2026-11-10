@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('heading', 'Dashboard Admin')

@section('content')
    <div class="admin-dashboard">
        <section class="dashboard-top-grid" aria-label="Informasi dashboard">
            <div class="dashboard-welcome">
                <div class="dashboard-welcome-copy">
                    <span class="dashboard-eyebrow">PANEL ADMINISTRATOR</span>
                    <h2>Selamat datang, Admin</h2>
                    <p>Pantau rekapitulasi kehadiran harian, kelola data akademik guru secara real-time, dan akses laporan penting dengan cepat dari satu dashboard.</p>
                </div>
                <div class="dashboard-welcome-actions">
                    <a class="btn btn-primary welcome-attendance-action" href="{{ route('admin.absensi', ['tgl' => $tanggal]) }}"><x-icon name="check" class="welcome-action-icon" /> Absensi Guru</a>
                    <a class="btn btn-outline welcome-report-action" href="{{ route('admin.laporan') }}"><x-icon name="chart" class="welcome-action-icon" /> Laporan</a>
                </div>
            </div>

            <aside class="dashboard-system-card" aria-label="Informasi sistem">
                <div class="dashboard-system-heading">
                    <span class="dashboard-system-icon"><x-icon name="school" /></span>
                    <div><span class="dashboard-panel-kicker">SISTEM INFORMASI</span><strong>SIAKAD SMA Pencawan</strong></div>
                </div>
                <div class="dashboard-system-date">
                    <span>Hari ini</span>
                    <time id="dashboard-live-date" datetime="{{ now()->toDateString() }}">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</time>
                    <span id="dashboard-live-time" class="dashboard-live-time">{{ now()->format('H:i') }} WIB</span>
                </div>
                <div class="dashboard-system-status">
                    <i aria-hidden="true"></i>
                    <span><strong>Sistem beroperasi normal</strong><small>Layanan aplikasi aktif</small></span>
                    <span class="dashboard-system-online">Aktif</span>
                </div>
                <div class="dashboard-system-version">
                    <span>Versi aplikasi</span>
                    <strong>Versi 1.0.0 - Development</strong>
                </div>
            </aside>
        </section>

        @if (session('sukses'))
            <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
        @endif

        <section class="metric-grid dashboard-metrics dashboard-metric-cards" aria-label="Ringkasan kehadiran hari ini">
            <article class="dashboard-metric-card metric-total">
                <span class="dashboard-metric-icon"><x-icon name="users" /></span>
                <div class="dashboard-metric-copy">
                    <span class="dashboard-metric-label">Total Guru Aktif</span>
                    <strong>{{ $totalGuru }} <small>Guru</small></strong>
                    <span class="dashboard-metric-hint">Terdaftar dan aktif mengajar</span>
                </div>
                <span class="dashboard-metric-decoration" aria-hidden="true">01</span>
            </article>
            <article class="dashboard-metric-card metric-present">
                <span class="dashboard-metric-icon"><x-icon name="check" /></span>
                <div class="dashboard-metric-copy">
                    <span class="dashboard-metric-label">Guru Hadir Hari Ini</span>
                    <strong>{{ $hadir }}</strong>
                    <span class="dashboard-metric-hint"><i class="presence-dot"></i> Presensi tepat waktu</span>
                </div>
                <span class="dashboard-metric-decoration" aria-hidden="true">02</span>
            </article>
            <article class="dashboard-metric-card metric-late">
                <span class="dashboard-metric-icon"><x-icon name="clock" /></span>
                <div class="dashboard-metric-copy">
                    <span class="dashboard-metric-label">Guru Terlambat</span>
                    <strong>{{ $terlambat }}</strong>
                    <span class="dashboard-metric-hint">Perlu tindak lanjut</span>
                </div>
                <span class="dashboard-metric-decoration" aria-hidden="true">03</span>
            </article>
            <article class="dashboard-metric-card metric-away">
                <span class="dashboard-metric-icon"><x-icon name="alert" /></span>
                <div class="dashboard-metric-copy">
                    <span class="dashboard-metric-label">Belum Hadir / Alpa</span>
                    <strong>{{ $belum }}</strong>
                    <span class="dashboard-metric-hint">Tidak hadir tanpa presensi masuk</span>
                    <span class="dashboard-metric-breakdown"><span>Izin <b>{{ $izin }}</b></span><span>Sakit <b>{{ $sakit }}</b></span><span>Alpa <b>{{ $alpa }}</b></span></span>
                </div>
                <span class="dashboard-metric-decoration" aria-hidden="true">04</span>
            </article>
        </section>

        <section class="dashboard-charts" aria-label="Grafik kehadiran guru">
            <article class="card dashboard-chart-card">
                <div class="dashboard-panel-heading">
                    <div>
                        <span class="dashboard-panel-kicker">TREN MINGGUAN</span>
                        <h3>Rekap Kehadiran Guru</h3>
                        <p>{{ $weekIsDemo ? 'Data contoh untuk pratinjau · bukan presensi aktual' : 'Perbandingan status kehadiran setiap hari · '.$weekLabel }}</p>
                    </div>
                    <span class="dashboard-panel-actions">
                        @if ($weekIsDemo)<span class="dashboard-demo-badge">DATA CONTOH</span>@endif
                        <span class="dashboard-panel-icon"><x-icon name="chart" /></span>
                    </span>
                </div>
                <div class="dashboard-chart-area">
                    <canvas id="weekly-attendance-chart" role="img" aria-label="Diagram batang rekap kehadiran guru selama satu minggu"></canvas>
                    <p class="dashboard-chart-empty" id="weekly-chart-empty" @if (collect($weekSeries)->flatten()->sum() > 0) hidden @endif>Belum ada data presensi pada minggu ini.</p>
                    <p class="dashboard-chart-empty" id="weekly-chart-error" hidden>Grafik tidak dapat dimuat. Periksa koneksi internet lalu muat ulang halaman.</p>
                </div>
            </article>

            <article class="card dashboard-chart-card dashboard-month-chart-card">
                <div class="dashboard-panel-heading">
                    <div>
                        <span class="dashboard-panel-kicker">RINGKASAN BULANAN</span>
                        <h3>Status Kehadiran</h3>
                        <p>{{ $monthIsDemo ? 'Data contoh untuk pratinjau · bukan presensi aktual' : 'Proporsi seluruh presensi tercatat · '.$monthLabel }}</p>
                    </div>
                    <span class="dashboard-panel-actions">
                        @if ($monthIsDemo)<span class="dashboard-demo-badge">DATA CONTOH</span>@endif
                        <span class="dashboard-panel-icon"><x-icon name="calendar" /></span>
                    </span>
                </div>
                <div class="dashboard-doughnut-area">
                    <canvas id="monthly-attendance-chart" role="img" aria-label="Diagram lingkaran persentase status kehadiran guru bulan ini"></canvas>
                    @if ($monthTotal > 0)
                        <div class="dashboard-doughnut-center"><strong>{{ $monthTotal }}</strong><span>catatan</span></div>
                    @else
                        <p class="dashboard-chart-empty" id="monthly-chart-empty">Belum ada data presensi bulan ini.</p>
                    @endif
                    <p class="dashboard-chart-empty" id="monthly-chart-error" hidden>Grafik tidak dapat dimuat. Periksa koneksi internet lalu muat ulang halaman.</p>
                </div>
                <div class="dashboard-chart-legend">
                    <span><i class="legend-hadir"></i>Hadir</span>
                    <span><i class="legend-terlambat"></i>Terlambat</span>
                    <span><i class="legend-izin"></i>Izin</span>
                    <span><i class="legend-sakit"></i>Sakit</span>
                    <span><i class="legend-alpa"></i>Alpa</span>
                </div>
            </article>
        </section>

        <section class="card dashboard-recent-card">
            <div class="card-title-flex dashboard-section-heading">
                <div>
                    <span class="dashboard-panel-kicker">AKTIVITAS TERKINI</span>
                    <h3 class="card-title">Presensi Terbaru</h3>
                    <p class="card-subtitle">Catatan presensi guru yang paling baru.</p>
                </div>
                <div class="dashboard-quick-links">
                    <a class="btn btn-outline btn-sm" href="{{ route('admin.absensi') }}">Absensi Guru <span aria-hidden="true">→</span></a>
                    <a class="btn btn-outline btn-sm" href="{{ route('admin.laporan') }}">Laporan <span aria-hidden="true">→</span></a>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data dashboard-activity-table">
                    <thead>
                    <tr>
                        <th>Nama Guru</th>
                        <th>Waktu Masuk</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recent as $item)
                        @php
                            $status = $item['status'] ?? '';
                            $statusLabel = $status === 'Hadir' ? 'Tepat Waktu' : $status;
                            $statusClass = match ($status) {
                                'Hadir' => 'is-on-time',
                                'Terlambat' => 'is-late',
                                'Sakit', 'Alpa', 'Belum Hadir', 'Belum Absen' => 'is-absent',
                                'Izin' => 'is-leave',
                                default => 'is-other',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="dashboard-person">
                                    <span class="dashboard-person-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($item['guru'] ?? '-', 0, 1)) }}</span>
                                    <span><strong>{{ $item['guru'] ?? '-' }}</strong><small>{{ \Illuminate\Support\Carbon::parse($item['tgl'])->locale('id')->translatedFormat('d M Y') }}</small></span>
                                </div>
                            </td>
                            <td>{{ $item['jam'] ?: '—' }}</td>
                            <td><span class="dashboard-status-badge {{ $statusClass }}"><i></i>{{ $statusLabel ?: 'Belum ada status' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="dashboard-empty">Belum ada aktivitas presensi yang dapat ditampilkan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        (() => {
            const weeklyCanvas = document.getElementById('weekly-attendance-chart');
            const monthlyCanvas = document.getElementById('monthly-attendance-chart');
            if (typeof Chart === 'undefined') {
                document.getElementById('weekly-chart-error').hidden = false;
                document.getElementById('weekly-chart-empty').hidden = true;
                document.getElementById('monthly-chart-error').hidden = false;
                const monthlyEmpty = document.getElementById('monthly-chart-empty');
                if (monthlyEmpty) monthlyEmpty.hidden = true;
                return;
            }

            const colors = {
                hadir: '#16a36a',
                terlambat: '#f59e0b',
                izin: '#3b82f6',
                sakit: '#8b5cf6',
                alpa: '#ef4444',
            };
            const weeklyLabels = @json($weekLabels);
            const weeklySeries = @json($weekSeries);
            const monthSeries = @json($monthSeries);
            const chartFont = { family: 'Inter, ui-sans-serif, system-ui, sans-serif' };

            if (weeklyCanvas) {
                new Chart(weeklyCanvas, {
                    type: 'bar',
                    data: {
                        labels: weeklyLabels,
                        datasets: [
                            { label: 'Hadir', data: weeklySeries.Hadir, backgroundColor: colors.hadir, borderRadius: 5, maxBarThickness: 18 },
                            { label: 'Terlambat', data: weeklySeries.Terlambat, backgroundColor: colors.terlambat, borderRadius: 5, maxBarThickness: 18 },
                            { label: 'Izin', data: weeklySeries.Izin, backgroundColor: colors.izin, borderRadius: 5, maxBarThickness: 18 },
                            { label: 'Sakit', data: weeklySeries.Sakit, backgroundColor: colors.sakit, borderRadius: 5, maxBarThickness: 18 },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, padding: 18, font: { ...chartFont, size: 11 } } },
                            tooltip: { backgroundColor: '#14213d', padding: 12, usePointStyle: true },
                        },
                        scales: {
                            x: { grid: { display: false }, border: { display: false }, ticks: { color: '#728096', font: { ...chartFont, size: 11 } } },
                            y: { beginAtZero: true, ticks: { precision: 0, stepSize: 1, color: '#8995a8', font: { ...chartFont, size: 10 } }, grid: { color: '#edf1f6', drawTicks: false }, border: { display: false } },
                        },
                    },
                });
            }

            if (monthlyCanvas && {{ $monthTotal }} > 0) {
                new Chart(monthlyCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa'],
                        datasets: [{
                            data: monthSeries,
                            backgroundColor: [colors.hadir, colors.terlambat, colors.izin, colors.sakit, colors.alpa],
                            borderColor: '#fff',
                            borderWidth: 4,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false },
                            tooltip: { backgroundColor: '#14213d', padding: 12, callbacks: { label: (context) => `${context.label}: ${context.raw} (${context.formattedValue ? (context.raw / {{ $monthTotal }} * 100).toFixed(1) : 0}%)` } },
                        },
                    },
                });
            }
        })();
    </script>
    <script>
        (() => {
            const dateElement = document.getElementById('dashboard-live-date');
            const timeElement = document.getElementById('dashboard-live-time');
            if (!dateElement || !timeElement) return;

            const updateClock = () => {
                const now = new Date();
                dateElement.dateTime = new Intl.DateTimeFormat('sv-SE').format(now);
                dateElement.textContent = new Intl.DateTimeFormat('id-ID', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                }).format(now);
                timeElement.textContent = `${new Intl.DateTimeFormat('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hourCycle: 'h23',
                }).format(now)} WIB`;
            };

            updateClock();
            window.setInterval(updateClock, 30000);
        })();
    </script>
@endsection
