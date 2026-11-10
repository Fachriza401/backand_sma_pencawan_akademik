@extends('layouts.guru')

@section('title', 'Dashboard Guru')

@section('content')
    <div class="guru-dashboard">
        @php
            $sesiAktif = collect($jadwalHari)->first(fn ($item) => sedang_berlangsung((int) ($item['jamKe'] ?? 0)));
        @endphp
        <section class="guru-welcome">
            <div class="guru-welcome-copy">
                <span class="guru-welcome-label"><x-icon name="graduation" /> PORTAL GURU</span>
                <h1>{{ $sapaan }}, {{ explode(',', $guru['nama'] ?? 'Guru')[0] }}!</h1>
                <p><span class="guru-welcome-subject">{{ $guru['mapel'] ?? 'Informasi akademik' }}</span><span aria-hidden="true">·</span>{{ \Illuminate\Support\Carbon::now()->locale('id')->translatedFormat('l, d F Y') }}</p>
            </div>
            <aside class="guru-welcome-status-widget" aria-label="Status guru hari ini">
                @if ($presensi)
                    <span class="guru-welcome-status-icon is-present"><x-icon name="check" /></span>
                    <span class="guru-welcome-status-copy">
                        <small>Presensi hari ini</small>
                        <strong>{{ $presensi['status'] ?? 'Hadir' }}</strong>
                        <span>Pukul {{ $presensi['jam'] ?? '-' }}</span>
                    </span>
                @elseif ($sesiAktif)
                    <span class="guru-welcome-status-icon is-live"><span class="guru-live-dot"></span></span>
                    <span class="guru-welcome-status-copy">
                        <small>Sesi Mengajar Aktif</small>
                        <strong>{{ $sesiAktif['kelas'] ?? 'Kelas' }} · {{ $sesiAktif['mapel'] ?? 'Mengajar' }}</strong>
                        <span>Berlangsung sekarang</span>
                    </span>
                @else
                    <span class="guru-welcome-status-icon is-pending"><x-icon name="clock" /></span>
                    <span class="guru-welcome-status-copy">
                        <small>Status hari ini</small>
                        <strong>Belum presensi</strong>
                        <span>Silakan catat kehadiran Anda</span>
                    </span>
                @endif
            </aside>
        </section>

        @if ($presensi)
            <section class="status-absen-box sudah guru-attendance-status">
                <div class="ic"><x-icon name="check" /></div>
                <div class="guru-status-copy">
                    <div class="t">Presensi hari ini sudah tercatat</div>
                    <div class="s">{{ $presensi['status'] ?? 'Hadir' }} · pukul {{ $presensi['jam'] ?? '-' }}</div>
                </div>
                <span class="guru-status-pill">Selesai</span>
            </section>
        @else
            <section class="status-absen-box belum guru-attendance-status">
                <div class="ic"><x-icon name="clock" /></div>
                <div class="guru-status-copy">
                    <div class="t">Jangan lupa presensi hari ini</div>
                    <div class="s">Catat kehadiran sebelum memulai kegiatan mengajar.</div>
                </div>
                @if ($fiturUnggulanAktif)
                    <button type="button" class="btn btn-sm guru-presence-cta" data-modal-open="dashboard-presence-modal" data-presence-open>Presensi Sekarang</button>
                @endif
            </section>
        @endif

        <section class="guru-shortcuts" aria-label="Pintasan menu guru">
            <a href="{{ route('guru.jadwal') }}" class="guru-shortcut">
                <span class="guru-shortcut-icon shortcut-violet"><x-icon name="calendar" /></span>
                <span class="guru-shortcut-copy"><strong>Jadwal</strong><small>Lihat agenda mengajar</small></span>
            </a>
            <a href="{{ route('guru.absensi-siswa') }}" class="guru-shortcut">
                <span class="guru-shortcut-icon shortcut-green"><x-icon name="check" /></span>
                <span class="guru-shortcut-copy"><strong>Absensi Siswa</strong><small>Kelola kehadiran kelas</small></span>
            </a>
            <a href="{{ route('guru.nilai') }}" class="guru-shortcut">
                <span class="guru-shortcut-icon shortcut-amber"><x-icon name="clipboard" /></span>
                <span class="guru-shortcut-copy"><strong>Nilai Siswa</strong><small>Kelola nilai dan catatan</small></span>
            </a>
            <a href="{{ route('guru.rapor') }}" class="guru-shortcut">
                <span class="guru-shortcut-icon shortcut-rose"><x-icon name="file" /></span>
                <span class="guru-shortcut-copy"><strong>Rekap Absensi</strong><small>Lihat laporan kehadiran</small></span>
            </a>
        </section>

        <section class="card guru-schedule-card">
            <div class="card-title-flex guru-section-heading">
                <div>
                    <span class="guru-section-kicker">AGENDA MENGAJAR</span>
                    <h2 class="card-title">Jadwal Hari Ini</h2>
                    <p class="guru-agenda-meta">Total {{ $jumlahKelasHariIni }} kelas hari ini · {{ $jumlahSesiHariIni }} sesi mengajar</p>
                </div>
                <a href="{{ route('guru.jadwal') }}" class="guru-text-link">Jadwal lengkap <span aria-hidden="true">→</span></a>
            </div>

            @forelse ($jadwalHari as $jadwal)
                @php
                    $jamMulai = $jadwal['mulai'] ?? '';
                    $jamSelesai = $jadwal['selesai'] ?? '';
                    $sedangBerlangsung = sedang_berlangsung((int) ($jadwal['jamKe'] ?? 0));
                @endphp
                <article class="jadwal-hari-item guru-timeline-item {{ $sedangBerlangsung ? 'sekarang' : '' }}"
                         data-dashboard-schedule
                         data-schedule-start="{{ $jamMulai }}"
                         data-schedule-end="{{ $jamSelesai }}"
                         data-schedule-date="{{ $tanggalHariIni }}">
                    <div class="jam">Jam {{ $jadwal['jamKe'] }}<br>{{ $jamMulai }}–{{ $jamSelesai }}</div>
                    <div class="isi">
                        <div class="m">{{ $jadwal['mapel'] ?? '-' }}</div>
                        <div class="k">{{ $jadwal['kelas'] ?? '-' }} <span aria-hidden="true">·</span> {{ $jadwal['ruang'] ?? '-' }}</div>
                    </div>
                    <span class="guru-now-badge" data-schedule-state>{{ $sedangBerlangsung ? 'Berlangsung' : 'Akan datang' }}</span>
                </article>
            @empty
                <div class="guru-empty-state">
                    <span class="guru-empty-icon"><x-icon name="calendar" /></span>
                    <strong>Tidak ada jadwal mengajar hari ini</strong>
                    <span>Gunakan waktu luang untuk menyiapkan kegiatan belajar.</span>
                </div>
            @endforelse
            @if ($jadwalHari !== [])
                <div class="guru-daily-progress" aria-label="Status administrasi mengajar hari ini">
                    <a href="{{ route('guru.absensi-siswa') }}" class="guru-progress-item">
                        <span class="guru-progress-icon progress-green"><x-icon name="check" /></span>
                        <span><strong>Absensi siswa</strong><small>{{ $jumlahAbsensiKelasTerisi }} dari {{ $jumlahKelasHariIni }} kelas terisi · {{ $jumlahAbsensiKelasPending }} tertunda</small></span>
                        <span class="guru-progress-arrow" aria-hidden="true">→</span>
                    </a>
                    <a href="{{ route('guru.nilai') }}" class="guru-progress-item">
                        <span class="guru-progress-icon progress-blue"><x-icon name="clipboard" /></span>
                        <span><strong>Input nilai</strong><small>{{ $jumlahNilaiTersimpan }} dari {{ $jumlahSesiHariIni }} kombinasi kelas/mapel tersimpan · {{ $jumlahNilaiPending }} belum tersimpan</small></span>
                        <span class="guru-progress-arrow" aria-hidden="true">→</span>
                    </a>
                </div>
            @endif
        </section>

        <section class="guru-analytics-grid" aria-label="Analitik aktivitas mengajar">
            <article class="card guru-chart-card">
                <div class="guru-chart-heading">
                    <div>
                        <span class="guru-section-kicker">KEHADIRAN SISWA</span>
                        <h2 class="card-title">Tren Mingguan</h2>
                        <p>Rekap status siswa pada kelas yang diampu, Senin sampai Minggu.</p>
                    </div>
                </div>
                <div class="guru-chart-wrap">
                    <canvas id="guru-weekly-attendance-chart" role="img" aria-label="Grafik tren mingguan status kehadiran siswa"></canvas>
                </div>
                <p class="guru-chart-fallback" id="guru-weekly-chart-fallback" hidden>Grafik tidak dapat dimuat. Periksa koneksi internet untuk memuat Chart.js.</p>
            </article>
            <article class="card guru-chart-card guru-progress-chart-card">
                <div class="guru-chart-heading">
                    <div>
                        <span class="guru-section-kicker">PROGRES ADMINISTRASI</span>
                        <h2 class="card-title">Absensi Kelas Hari Ini</h2>
                        <p>Progres berdasarkan kelas pada agenda hari ini.</p>
                    </div>
                </div>
                @if ($jumlahKelasHariIni > 0)
                    <div class="guru-doughnut-wrap">
                        <canvas id="guru-teaching-progress-chart" role="img" aria-label="Grafik persentase absensi kelas terisi dan tertunda"></canvas>
                        <div class="guru-doughnut-center"><strong>{{ (int) round($jumlahAbsensiKelasTerisi / $jumlahKelasHariIni * 100) }}%</strong><span>terisi</span></div>
                    </div>
                    <div class="guru-progress-legend">
                        <span><i class="guru-legend-dot is-done"></i>Selesai <strong>{{ $jumlahAbsensiKelasTerisi }}</strong></span>
                        <span><i class="guru-legend-dot is-pending"></i>Belum diisi <strong>{{ $jumlahAbsensiKelasPending }}</strong></span>
                    </div>
                @else
                    <div class="guru-chart-empty">Belum ada kelas pada agenda hari ini.</div>
                @endif
                <p class="guru-chart-fallback" id="guru-progress-chart-fallback" hidden>Grafik tidak dapat dimuat. Periksa koneksi internet untuk memuat Chart.js.</p>
            </article>
        </section>

        <section class="card guru-announcements" id="informasi-terbaru">
            <div class="guru-section-heading">
                <span class="guru-section-kicker">DARI SEKOLAH</span>
                <h2 class="card-title">Informasi Terbaru</h2>
                <p class="card-subtitle">Pengumuman resmi dari administrator.</p>
            </div>
            <div class="guru-announcement-list" data-live-announcements>
                @forelse ($informasiTerbaru as $informasi)
                    <article class="guru-announcement">
                        <div class="guru-announcement-top">
                            <h3>{{ $informasi['judul'] ?? 'Informasi sekolah' }}</h3>
                            <small>{{ isset($informasi['publishedAt']) ? \Illuminate\Support\Carbon::parse($informasi['publishedAt'])->locale('id')->translatedFormat('d M Y') : '' }}</small>
                        </div>
                        <div class="info-meta">
                            {!! badge_status($informasi['urgensi'] ?? 'Normal') !!}
                            @if (! empty($informasi['pengirim']))
                                <span>Dari {{ $informasi['pengirim'] }}</span>
                            @endif
                            @if (! empty($informasi['targetKelas']))
                                <span>Untuk {{ $informasi['targetKelas'] }}</span>
                            @endif
                        </div>
                        <p>{{ $informasi['isi'] ?? '' }}</p>
                    </article>
                @empty
                    <p class="guru-announcement-empty">Belum ada pengumuman yang dipublikasikan.</p>
                @endforelse
            </div>
        </section>
    </div>

    @if (!$presensi && $fiturUnggulanAktif)
        <div class="modal-backdrop" id="dashboard-presence-modal" aria-hidden="true">
            <section class="modal guru-presence-modal" role="dialog" aria-modal="true" aria-labelledby="dashboard-presence-title">
                <div class="modal-head">
                    <div>
                        <h3 id="dashboard-presence-title">Presensi Hari Ini</h3>
                        <p class="modal-subtitle">Izinkan akses lokasi untuk mencatat kehadiran dari dashboard.</p>
                    </div>
                    <button type="button" class="modal-close" data-modal-close="dashboard-presence-modal" aria-label="Tutup">×</button>
                </div>
                <form method="POST" action="{{ route('guru.presensi.store') }}" enctype="multipart/form-data" id="dashboard-presence-form">
                    @csrf
                    <div class="modal-body">
                        <div class="guru-location-status" id="dashboard-location-status" role="status" aria-live="polite">Lokasi belum diambil.</div>
                        <button type="button" class="btn btn-outline btn-sm" id="dashboard-get-location">Ambil Lokasi Saya</button>
                        <input type="hidden" name="lat" id="dashboard-presence-lat" required>
                        <input type="hidden" name="lng" id="dashboard-presence-lng" required>
                        <input type="hidden" name="jarak" id="dashboard-presence-distance" required>
                        @error('lat') <small class="form-error" style="display:block;">{{ $message }}</small> @enderror
                        @error('lng') <small class="form-error" style="display:block;">{{ $message }}</small> @enderror
                        @error('jarak') <small class="form-error" style="display:block;">{{ $message }}</small> @enderror
                        <div class="form-group guru-presence-photo">
                            <label class="form-label" for="dashboard-presence-photo">Bukti Foto (opsional)</label>
                            <input class="form-input" type="file" id="dashboard-presence-photo" name="buktiFoto[]" accept="image/jpeg,image/png,image/webp" capture="environment" multiple>
                            <small class="form-hint">Maksimal 3 foto, JPG/PNG/WEBP, 2 MB per foto.</small>
                            @error('buktiFoto') <small class="form-error" style="display:block;">{{ $message }}</small> @enderror
                            @error('buktiFoto.*') <small class="form-error" style="display:block;">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button type="button" class="btn btn-outline" data-modal-close="dashboard-presence-modal">Batal</button>
                        <button type="submit" class="btn guru-presence-submit" id="dashboard-submit-presence" disabled>Kirim Presensi</button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        (() => {
            const weeklyCanvas = document.getElementById('guru-weekly-attendance-chart');
            const weeklyFallback = document.getElementById('guru-weekly-chart-fallback');
            const progressCanvas = document.getElementById('guru-teaching-progress-chart');
            const progressFallback = document.getElementById('guru-progress-chart-fallback');

            if (!window.Chart) {
                if (weeklyFallback) weeklyFallback.hidden = false;
                if (progressFallback && progressCanvas) progressFallback.hidden = false;
                return;
            }

            if (weeklyCanvas) {
                new Chart(weeklyCanvas, {
                    type: 'bar',
                    data: {
                        labels: @json($weeklyAttendance['labels']),
                        datasets: [
                            { label: 'Hadir', data: @json($weeklyAttendance['hadir']), backgroundColor: '#16a34a', borderRadius: 4 },
                            { label: 'Izin', data: @json($weeklyAttendance['izin']), backgroundColor: '#3b82f6', borderRadius: 4 },
                            { label: 'Sakit', data: @json($weeklyAttendance['sakit']), backgroundColor: '#f59e0b', borderRadius: 4 },
                            { label: 'Alpa', data: @json($weeklyAttendance['alpa']), backgroundColor: '#e11d48', borderRadius: 4 },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 14 } } },
                        scales: {
                            x: { stacked: true, grid: { display: false } },
                            y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#e2e8f0' } },
                        },
                    },
                });
            }

            if (progressCanvas) {
                new Chart(progressCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Selesai', 'Belum diisi'],
                        datasets: [{
                            data: [{{ $jumlahAbsensiKelasTerisi }}, {{ $jumlahAbsensiKelasPending }}],
                            backgroundColor: ['#16a34a', '#e2e8f0'],
                            borderWidth: 0,
                            hoverOffset: 5,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '76%',
                        plugins: { legend: { display: false } },
                    },
                });
            }
        })();
    </script>
    @if (!$presensi && $fiturUnggulanAktif)
        <script>
            (() => {
                const trigger = document.querySelector('[data-presence-open]');
                const locateButton = document.getElementById('dashboard-get-location');
                const status = document.getElementById('dashboard-location-status');
                const submit = document.getElementById('dashboard-submit-presence');
                const latInput = document.getElementById('dashboard-presence-lat');
                const lngInput = document.getElementById('dashboard-presence-lng');
                const distanceInput = document.getElementById('dashboard-presence-distance');
                const school = @json($koordinatSekolah);

                const distanceInMeters = (lat1, lng1, lat2, lng2) => {
                    const radians = (degrees) => degrees * Math.PI / 180;
                    const deltaLat = radians(lat2 - lat1);
                    const deltaLng = radians(lng2 - lng1);
                    const value = Math.sin(deltaLat / 2) ** 2
                        + Math.cos(radians(lat1)) * Math.cos(radians(lat2)) * Math.sin(deltaLng / 2) ** 2;
                    return 6371000 * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
                };

                const getLocation = () => {
                    if (!navigator.geolocation) {
                        status.textContent = 'Browser ini tidak mendukung akses lokasi.';
                        return;
                    }

                    locateButton.disabled = true;
                    submit.disabled = true;
                    status.textContent = 'Sedang mengambil lokasi perangkat...';
                    navigator.geolocation.getCurrentPosition((position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const distance = distanceInMeters(lat, lng, Number(school.lat), Number(school.lng));
                        latInput.value = lat.toFixed(6);
                        lngInput.value = lng.toFixed(6);
                        distanceInput.value = distance.toFixed(1);
                        status.textContent = `Lokasi didapat · sekitar ${Math.round(distance)} meter dari sekolah (radius ${school.radius} meter).`;
                        submit.disabled = false;
                        locateButton.disabled = false;
                    }, (error) => {
                        const message = error.code === 1
                            ? 'Akses lokasi ditolak. Izinkan lokasi pada browser lalu coba lagi.'
                            : 'Lokasi tidak dapat diambil. Pastikan GPS aktif lalu coba kembali.';
                        status.textContent = message;
                        locateButton.disabled = false;
                    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
                };

                trigger?.addEventListener('click', () => window.setTimeout(getLocation, 0));
                locateButton?.addEventListener('click', getLocation);
            })();
        </script>
    @endif
    @if (!$presensi && $fiturUnggulanAktif && ($errors->has('jarak') || $errors->has('lat') || $errors->has('lng') || $errors->has('buktiFoto') || $errors->has('buktiFoto.*')))
        <script>
            window.addEventListener('DOMContentLoaded', () => window.SigapModal.open('dashboard-presence-modal'));
        </script>
    @endif
    <script>
        window.addEventListener('notifications:updated', (event) => {
            const feed = document.querySelector('[data-live-announcements]');
            const items = event.detail?.items;
            if (!feed || !Array.isArray(items)) return;

            feed.replaceChildren();
            if (!items.length) {
                const empty = document.createElement('p');
                empty.className = 'guru-announcement-empty';
                empty.textContent = 'Belum ada pengumuman yang dipublikasikan.';
                feed.append(empty);
                return;
            }

            items.forEach((item) => {
                const article = document.createElement('article');
                article.className = 'guru-announcement';
                const top = document.createElement('div');
                top.className = 'guru-announcement-top';
                const heading = document.createElement('h3');
                heading.textContent = item.title || 'Informasi sekolah';
                top.append(heading);

                const meta = document.createElement('div');
                meta.className = 'info-meta';
                const urgency = document.createElement('span');
                const badgeClass = item.urgensi === 'Penting' ? 'emas' : (item.urgensi === 'Rendah' ? 'izin' : 'netral');
                urgency.className = `badge badge-${badgeClass}`;
                urgency.textContent = item.urgensi || 'Normal';
                meta.append(urgency);

                if (item.pengirim) {
                    const sender = document.createElement('span');
                    sender.textContent = `Dari ${item.pengirim}`;
                    meta.append(sender);
                }
                if (item.targetKelas) {
                    const target = document.createElement('span');
                    target.textContent = `Untuk ${item.targetKelas}`;
                    meta.append(target);
                }

                const message = document.createElement('p');
                message.textContent = item.message || '';
                article.append(top, meta, message);
                feed.append(article);
            });
        });
    </script>
    <script>
        (() => {
            const schedules = [...document.querySelectorAll('[data-dashboard-schedule]')];
            if (!schedules.length) return;

            const refreshScheduleState = () => {
                const now = new Date();
                const today = [
                    now.getFullYear(),
                    String(now.getMonth() + 1).padStart(2, '0'),
                    String(now.getDate()).padStart(2, '0'),
                ].join('-');
                if (schedules[0].dataset.scheduleDate !== today) {
                    window.location.reload();
                    return;
                }

                const currentMinutes = now.getHours() * 60 + now.getMinutes();
                schedules.forEach((schedule) => {
                    const [startHour, startMinute] = schedule.dataset.scheduleStart.split(':').map(Number);
                    const [endHour, endMinute] = schedule.dataset.scheduleEnd.split(':').map(Number);
                    const start = startHour * 60 + startMinute;
                    const end = endHour * 60 + endMinute;
                    const ongoing = currentMinutes >= start && currentMinutes < end;
                    const finished = currentMinutes >= end;
                    schedule.classList.toggle('sekarang', ongoing);
                    const state = schedule.querySelector('[data-schedule-state]');
                    state.textContent = ongoing ? 'Berlangsung' : (finished ? 'Selesai' : 'Akan datang');
                    state.classList.toggle('is-finished', finished);
                });
            };

            refreshScheduleState();
            window.setInterval(refreshScheduleState, 30000);
        })();
    </script>
@endsection
