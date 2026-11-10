@extends('layouts.guru')

@section('title', 'Jadwal Guru')

@section('content')
    <div class="guru-page-heading">
        <div>
            <span class="guru-page-kicker">PERENCANAAN MENGAJAR</span>
            <h2>Jadwal Mengajar</h2>
            <p>{{ $hariTerpilih }}, {{ \Illuminate\Support\Carbon::parse($tanggal)->locale('id')->translatedFormat('d F Y') }} · Jadwal kelas yang Anda ajar.</p>
        </div>
        <div class="guru-schedule-heading-actions no-print">
            <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v6H7z"/></svg>
                Cetak Jadwal
            </button>
            <div class="guru-page-heading-mark"><x-icon name="calendar" /></div>
        </div>
    </div>

    <div class="card guru-page-card">
        <nav class="teacher-schedule-day-tabs no-print" aria-label="Pilih hari jadwal">
            @foreach ($hari as $namaHari)
                <a href="{{ route('guru.jadwal', array_filter(['tanggal' => $tabDates[$namaHari], 'kelas' => $kelasFilter])) }}"
                   class="{{ $hariFilter === $namaHari ? 'active' : '' }}"
                   @if ($hariFilter === $namaHari) aria-current="page" @endif>{{ $namaHari }}</a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('guru.jadwal') }}" class="guru-filter-panel schedule-date-filter no-print">
            <input type="hidden" name="hari" value="{{ $hariFilter }}">
            <div class="form-group">
                <label class="form-label" for="jadwal-tanggal">Pilih Tanggal</label>
                <input class="form-input" id="jadwal-tanggal" name="tanggal" type="date" value="{{ $tanggal }}" onchange="this.form.hari.value=''; this.form.submit()">
            </div>
            <div class="form-group">
                <label class="form-label" for="jadwal-kelas">Filter Kelas</label>
                <select class="form-select" id="jadwal-kelas" name="kelas" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach ($kelasTersedia as $kelas)
                        <option value="{{ $kelas }}" @selected($kelasFilter === $kelas)>{{ $kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="guru-count-chip">{{ count($semuaJadwal) }} sesi · {{ count(array_unique(array_column($semuaJadwal, 'kelas'))) }} kelas</div>
        </form>

        <div class="guru-table-heading">
            <div>
                <span class="guru-section-kicker">AGENDA HARIAN</span>
                <h3 class="card-title">Jadwal Hari {{ $hariTerpilih }}</h3>
            </div>
        </div>

        <div class="jadwal-grid-wrap">
            @forelse ($jadwalHari as $slot)
                @php
                    $jamSlot = collect($jam)->firstWhere('ke', (int) ($slot['jamKe'] ?? 0));
                    $tanggalBelumLewat = $tanggal <= now()->toDateString();
                @endphp
                <article class="teacher-day-schedule"
                         data-teacher-schedule
                         data-schedule-date="{{ $tanggal }}"
                         data-schedule-start="{{ $jamSlot['mulai'] ?? '' }}"
                         data-schedule-end="{{ $jamSlot['selesai'] ?? '' }}">
                    <div class="teacher-day-time">
                        <strong>Jam {{ $slot['jamKe'] }}</strong>
                        <span>{{ $jamSlot['mulai'] ?? '--:--' }}–{{ $jamSlot['selesai'] ?? '--:--' }}</span>
                    </div>
                    <div class="teacher-day-details">
                        <span class="teacher-day-subject">{{ $slot['mapel'] ?? '-' }}</span>
                        <span>Kelas {{ $slot['kelas'] ?? '-' }}</span>
                        <span>Ruang {{ $slot['ruang'] ?? '-' }}</span>
                    </div>
                    <div class="teacher-day-actions no-print">
                        <span class="teacher-day-status {{ $slot['statusWaktu'] === 'Sedang Berlangsung' ? 'is-live' : ($slot['statusWaktu'] === 'Selesai' ? 'is-finished' : 'is-upcoming') }}" data-schedule-status>{{ $slot['statusWaktu'] }}</span>
                        @if ($tanggalBelumLewat)
                            <a class="btn btn-sm teacher-attendance-action" href="{{ route('guru.absensi-siswa', ['kelas' => $slot['kelas'], 'tanggal' => $tanggal]) }}">
                                <x-icon name="check" /> Absen Siswa
                            </a>
                        @else
                            <span class="teacher-attendance-disabled">Absen belum tersedia</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="guru-empty-state">
                    <span class="guru-empty-icon"><x-icon name="calendar" /></span>
                    <strong>{{ $akhirPekan ? 'Hari Libur' : 'Tidak ada jadwal pada tanggal ini' }}</strong>
                    <span>{{ $akhirPekan ? 'Jadwal mengajar berlangsung pada hari sekolah.' : 'Pilih tanggal lain untuk melihat agenda mengajar.' }}</span>
                </div>
            @endforelse
        </div>
    </div>
    <script>
        (() => {
            const schedules = [...document.querySelectorAll('[data-teacher-schedule]')];
            if (!schedules.length) return;

            const updateStatuses = () => {
                const now = new Date();
                const today = [
                    now.getFullYear(),
                    String(now.getMonth() + 1).padStart(2, '0'),
                    String(now.getDate()).padStart(2, '0'),
                ].join('-');

                schedules.forEach((schedule) => {
                    const date = schedule.dataset.scheduleDate;
                    const [startHour, startMinute] = schedule.dataset.scheduleStart.split(':').map(Number);
                    const [endHour, endMinute] = schedule.dataset.scheduleEnd.split(':').map(Number);
                    const state = schedule.querySelector('[data-schedule-status]');
                    if (!state || Number.isNaN(startHour) || Number.isNaN(endHour)) return;

                    let label = 'Akan Datang';
                    if (date < today) {
                        label = 'Selesai';
                    } else if (date === today) {
                        const currentMinutes = now.getHours() * 60 + now.getMinutes();
                        const start = startHour * 60 + startMinute;
                        const end = endHour * 60 + endMinute;
                        label = currentMinutes < start ? 'Akan Datang' : (currentMinutes < end ? 'Sedang Berlangsung' : 'Selesai');
                    }

                    state.textContent = label;
                    state.classList.toggle('is-live', label === 'Sedang Berlangsung');
                    state.classList.toggle('is-upcoming', label === 'Akan Datang');
                    state.classList.toggle('is-finished', label === 'Selesai');
                });
            };

            updateStatuses();
            window.setInterval(updateStatuses, 30000);
        })();
    </script>
@endsection
