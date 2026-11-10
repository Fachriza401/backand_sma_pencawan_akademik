<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('assets/logo-pencawan.png') }}" type="image/png">
    <title>@yield('title', 'Guru SMA Pencawan')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<div class="guru-shell">
    <aside class="guru-sidebar">
        <a class="guru-sidebar-brand" href="{{ route('guru.dashboard') }}">
            <img class="tb-logo" src="{{ asset('assets/logo-pencawan.png') }}" alt="">
            <div>
                <p class="judul">SIAKAD</p>
                <div class="sub">SMA Pencawan</div>
                <small class="guru-brand-description">Sistem Informasi Akademik</small>
            </div>
        </a>
        <nav class="guru-side-nav" aria-label="Menu guru">
            <a class="{{ request()->routeIs('guru.dashboard') ? 'active' : '' }}" href="{{ route('guru.dashboard') }}"><x-icon name="home" /> Beranda</a>
            <a class="{{ request()->routeIs('guru.jadwal') ? 'active' : '' }}" href="{{ route('guru.jadwal') }}"><x-icon name="calendar" /> Jadwal</a>
            @if (App\Support\Sekolah::pengaturan()['fiturUnggulanGuruAktif'] ?? true)
                <a class="{{ request()->routeIs('guru.presensi') ? 'active' : '' }}" href="{{ route('guru.presensi') }}"><x-icon name="pin" /> Presensi Guru</a>
            @endif
            <a class="{{ request()->routeIs('guru.absensi-siswa*') ? 'active' : '' }}" href="{{ route('guru.absensi-siswa') }}"><x-icon name="check" /> Absensi Siswa</a>
            <a class="{{ request()->routeIs('guru.nilai*') ? 'active' : '' }}" href="{{ route('guru.nilai') }}"><x-icon name="clipboard" /> Nilai Siswa</a>
            <a class="{{ request()->routeIs('guru.rapor') ? 'active' : '' }}" href="{{ route('guru.rapor') }}"><x-icon name="file" /> Rekap Absensi</a>
        </nav>
        <div class="guru-sidebar-foot guru-version">Versi 1.0.0 - Development</div>
    </aside>

    <div class="guru-main">
        <header class="guru-topbar">
            <div class="guru-mobile-brand">
                <img class="tb-logo" src="{{ asset('assets/logo-pencawan.png') }}" alt="">
                <div>
                    <p class="judul">SIAKAD <span class="guru-brand-school">· SMA Pencawan</span></p>
                    <div class="sub">Sistem Informasi Akademik</div>
                </div>
            </div>
            <div class="guru-desktop-heading">
                <span>Portal Guru</span>
                <strong>@yield('title', 'SMA Pencawan')</strong>
            </div>
            <div class="guru-topbar-user">
                <span>{{ session('user.nama', 'Guru') }}</span>
                <x-notification-menu :url="route('guru.notifikasi')" label="Informasi terbaru" />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm"><x-icon name="logout" /> Keluar</button>
                </form>
            </div>
        </header>

        <main class="guru-content">
            @yield('content')
        </main>

        <nav class="bottom-nav" aria-label="Menu guru">
            <a class="{{ request()->routeIs('guru.dashboard') ? 'active' : '' }}" href="{{ route('guru.dashboard') }}"><x-icon name="home" /> Beranda</a>
            <a class="{{ request()->routeIs('guru.jadwal') ? 'active' : '' }}" href="{{ route('guru.jadwal') }}"><x-icon name="calendar" /> Jadwal</a>
            @if (App\Support\Sekolah::pengaturan()['fiturUnggulanGuruAktif'] ?? true)
                <a class="{{ request()->routeIs('guru.presensi') ? 'active' : '' }}" href="{{ route('guru.presensi') }}"><x-icon name="pin" /> Presensi</a>
            @endif
            <a class="{{ request()->routeIs('guru.absensi-siswa*') ? 'active' : '' }}" href="{{ route('guru.absensi-siswa') }}"><x-icon name="check" /> Absensi</a>
            <a class="{{ request()->routeIs('guru.nilai*') ? 'active' : '' }}" href="{{ route('guru.nilai') }}"><x-icon name="clipboard" /> Nilai</a>
            <a class="{{ request()->routeIs('guru.rapor') ? 'active' : '' }}" href="{{ route('guru.rapor') }}"><x-icon name="file" /> Rekap</a>
        </nav>
    </div>
</div>

<div class="toast-stack" data-guru-toast-stack aria-live="polite" aria-atomic="false"></div>

<script>
    (() => {
        const stack = document.querySelector('[data-guru-toast-stack]');
        if (!stack) return;

        let jumlahAwal = null;
        let idDikenal = new Set();

        const tampilkanToast = (item) => {
            const toast = document.createElement('div');
            toast.className = 'toast info';
            const body = document.createElement('div');
            const title = document.createElement('strong');
            const message = document.createElement('span');
            title.textContent = item.title || 'Informasi baru';
            const teks = (item.message || '').trim();
            message.textContent = teks.length > 120 ? teks.slice(0, 117) + '...' : teks;
            body.append(title, message);
            toast.append(body);
            stack.append(toast);

            window.setTimeout(() => {
                toast.classList.add('out');
                window.setTimeout(() => toast.remove(), 260);
            }, 6500);
        };

        window.addEventListener('notifications:updated', (event) => {
            const items = Array.isArray(event.detail?.items) ? event.detail.items : [];
            const jumlah = Number(event.detail?.count) || items.length;

            if (jumlahAwal === null) {
                jumlahAwal = jumlah;
                idDikenal = new Set(items.map((item) => String(item.id || '')));
                return;
            }

            const baru = items.filter((item) => !idDikenal.has(String(item.id || '')));
            if (jumlah > jumlahAwal && baru.length) {
                baru.slice(0, 2).forEach(tampilkanToast);
            }

            jumlahAwal = jumlah;
            idDikenal = new Set(items.map((item) => String(item.id || '')));
        });
    })();
</script>
</body>
</html>
