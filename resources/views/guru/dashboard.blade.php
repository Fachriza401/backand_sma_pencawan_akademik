@extends('layouts.guru')

@section('title', 'Dashboard Guru')

@section('content')
    <div class="sapaan">
        <h3>{{ $sapaan }}, {{ $guru['nama'] ?? 'Guru' }}!</h3>
        <p>{{ $sekolah['nama'] ?? 'SMA Pencawan' }} • {{ date('d M Y') }}</p>
    </div>

    @php $presensiHari = $presensi; @endphp
    @if ($presensiHari)
        <div class="status-absen-box sudah">
            <div class="ic"><x-icon name="check" /></div>
            <div>
                <div class="t">Anda sudah absen hari ini</div>
                <div class="s">{{ $presensiHari['status'] ?? 'Hadir' }} • {{ $presensiHari['jam'] ?? '-' }}</div>
            </div>
        </div>
    @else
        <div class="status-absen-box belum">
            <div class="ic"><x-icon name="clock" /></div>
            <div>
                <div class="t">Anda belum melakukan absensi</div>
                <div class="s">Silakan isi presensi untuk hari ini.</div>
            </div>
        </div>
    @endif

    <div class="shortcut-grid">
        @if ($fiturUnggulanAktif)
            <a href="{{ route('guru.presensi') }}" class="shortcut"><x-icon name="pin" /> Presensi</a>
        @endif
        <a href="{{ route('guru.jadwal') }}" class="shortcut"><x-icon name="calendar" /> Jadwal</a>
        <a href="{{ route('guru.absensi-siswa') }}" class="shortcut"><x-icon name="check" /> Absensi Siswa</a>
        <a href="{{ route('guru.nilai') }}" class="shortcut"><x-icon name="clipboard" /> Nilai</a>
        <a href="{{ route('guru.rapor') }}" class="shortcut"><x-icon name="file" /> Rapor</a>
    </div>

    <div class="card">
        <div class="card-title-flex">
            <h3 class="card-title">Jadwal Hari Ini</h3>
            <span style="font-size:12px; color:var(--teks-2);">{{ hari_ini() }}</span>
        </div>

        @forelse ($jadwalHari as $jadwal)
            <div class="jadwal-hari-item {{ sedang_berlangsung((int)($jadwal['jamKe'] ?? 0)) ? 'sekarang' : '' }}">
                <div class="jam">Jam {{ $jadwal['jamKe'] }}<br>{{ $jadwal['jam'] ?? '' }}</div>
                <div class="isi">
                    <div class="m">{{ $jadwal['mapel'] ?? '-' }}</div>
                    <div class="k">{{ $jadwal['kelas'] ?? '-' }} • {{ $jadwal['ruang'] ?? '-' }}</div>
                </div>
            </div>
        @empty
            <div class="alert alert-biru">Tidak ada jadwal untuk hari ini.</div>
        @endforelse
    </div>

    <section class="card" id="informasi-terbaru" style="margin-top:18px;">
        <div class="card-title-flex">
            <div>
                <h3 class="card-title" style="margin-bottom:4px;">Informasi Terbaru</h3>
                <p style="margin:0; color:var(--teks-2); font-size:12.5px;">Pengumuman resmi dari administrator. Daftar diperbarui otomatis.</p>
            </div>
        </div>
        <div data-live-announcements>
            @forelse ($informasiTerbaru as $informasi)
                <article style="padding:14px 0; border-top:1px solid var(--garis);">
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                        <h4 style="margin:0; color:var(--biru-tua);">{{ $informasi['judul'] ?? 'Informasi sekolah' }}</h4>
                        <small style="color:var(--teks-2);">{{ isset($informasi['publishedAt']) ? \Illuminate\Support\Carbon::parse($informasi['publishedAt'])->format('d/m/Y H:i') : '' }}</small>
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
                    <p style="white-space:pre-wrap; margin:8px 0 0; color:var(--teks-2);">{{ $informasi['isi'] ?? '' }}</p>
                </article>
            @empty
                <p style="color:var(--teks-2); border-top:1px solid var(--garis); padding-top:14px; margin-bottom:0;">Belum ada pengumuman yang dipublikasikan.</p>
            @endforelse
        </div>
    </section>

    <script>
        window.addEventListener('notifications:updated', (event) => {
            const feed = document.querySelector('[data-live-announcements]');
            if (!feed || !Array.isArray(event.detail.items)) return;

            feed.replaceChildren();
            if (!event.detail.items.length) {
                const empty = document.createElement('p');
                empty.style.cssText = 'color:var(--teks-2); border-top:1px solid var(--garis); padding-top:14px; margin-bottom:0;';
                empty.textContent = 'Belum ada pengumuman yang dipublikasikan.';
                feed.append(empty);
                return;
            }

            event.detail.items.forEach((item) => {
                const article = document.createElement('article');
                article.style.cssText = 'padding:14px 0; border-top:1px solid var(--garis);';
                const heading = document.createElement('h4');
                heading.style.cssText = 'margin:0; color:var(--biru-tua);';
                heading.textContent = item.title || 'Informasi sekolah';
                const meta = document.createElement('div');
                meta.className = 'info-meta';
                const urgency = document.createElement('span');
                const kelasBadge = item.urgensi === 'Penting' ? 'emas' : (item.urgensi === 'Rendah' ? 'izin' : 'netral');
                urgency.className = 'badge badge-'.$kelasBadge;
                urgency.textContent = item.urgensi || 'Normal';
                meta.append(urgency);
                if (item.pengirim) {
                    const sender = document.createElement('span');
                    sender.textContent = 'Dari '.item.pengirim;
                    meta.append(sender);
                }
                if (item.targetKelas) {
                    const target = document.createElement('span');
                    target.textContent = 'Untuk '.item.targetKelas;
                    meta.append(target);
                }
                const message = document.createElement('p');
                message.style.cssText = 'white-space:pre-wrap; margin:8px 0 0; color:var(--teks-2);';
                message.textContent = item.message || '';
                article.append(heading, meta, message);
                feed.append(article);
            });
        });
    </script>
@endsection
