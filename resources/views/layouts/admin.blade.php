<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('assets/logo-pencawan.png') }}" type="image/png">
    <title>@yield('title', 'SMA Pencawan')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="logo-box">
                <img src="{{ asset('assets/logo-pencawan.png') }}" alt="Logo SMA Pencawan">
            </div>
            <div class="admin-brand-copy">
                <strong>SIGAP</strong>
                <span>SMA Pencawan</span>
                <small>Sistem Informasi Kehadiran &amp; Agenda Pengajar</small>
            </div>
        </div>

        <nav>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><x-icon name="home" /> Dashboard</a>
            <a href="{{ route('admin.guru') }}" class="{{ request()->routeIs('admin.guru') ? 'active' : '' }}"><x-icon name="users" /> Data Guru</a>
            <a href="{{ route('admin.siswa') }}" class="{{ request()->routeIs('admin.siswa*') ? 'active' : '' }}"><x-icon name="graduation" /> Data Siswa</a>
            <a href="{{ route('admin.jadwal-guru') }}" class="{{ request()->routeIs('admin.jadwal-guru') ? 'active' : '' }}"><x-icon name="calendar" /> Jadwal Guru</a>
            <a href="{{ route('admin.jadwal-kelas') }}" class="{{ request()->routeIs('admin.jadwal-kelas') ? 'active' : '' }}"><x-icon name="book" /> Jadwal Kelas</a>
            <a href="{{ route('admin.absensi') }}" class="{{ request()->routeIs('admin.absensi') ? 'active' : '' }}"><x-icon name="check" /> Absensi Guru</a>
            <a href="{{ route('admin.laporan') }}" class="{{ request()->routeIs('admin.laporan') ? 'active' : '' }}"><x-icon name="chart" /> Laporan</a>
            <a href="{{ route('admin.informasi') }}" class="{{ request()->routeIs('admin.informasi') ? 'active' : '' }}"><x-icon name="file" /> Manajemen Informasi</a>
            <a href="{{ route('admin.pemulihan-akun') }}" class="{{ request()->routeIs('admin.pemulihan-akun*') ? 'active' : '' }}"><x-icon name="alert" /> Pemulihan Akun</a>
        </nav>

    </aside>

    <div class="main-area">
        <header class="topbar">
            <div class="topbar-left">
                <button class="burger" type="button" aria-label="Toggle menu">☰</button>
                <div class="topbar-brand-copy">
                    <h1>SIGAP <span>· SMA Pencawan</span></h1>
                    <p>Sistem Informasi Kehadiran &amp; Agenda Pengajar</p>
                </div>
            </div>

            <div class="topbar-user">
                <x-notification-menu :url="route('admin.notifikasi')" label="Notifikasi Admin" />
                <details class="admin-user-menu">
                    <summary>
                        <span>
                            <span class="nama">{{ session('user.nama') ?? 'Administrator' }}</span>
                            <span class="role">ADMINISTRATOR</span>
                        </span>
                        <span aria-hidden="true">⌄</span>
                    </summary>
                    <div class="admin-user-dropdown">
                        <a href="{{ route('admin.profil') }}">Profil Admin</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">Keluar</button>
                        </form>
                    </div>
                </details>
            </div>
        </header>

        <main class="page-content">
            @yield('content')
        </main>
        <footer class="admin-version-footer">Versi 1.0.0 - Development</footer>
    </div>
</div>

<script>
    const burger = document.querySelector('.burger');
    const sidebar = document.querySelector('.sidebar');
    if (burger && sidebar) {
        burger.addEventListener('click', () => {
            sidebar.style.transform = sidebar.style.transform === 'translateX(-100%)' ? 'translateX(0)' : 'translateX(-100%)';
        });
    }

    window.SigapModal = {
        open(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        },
        close(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.modal-backdrop.show, .drawer-backdrop.show')) {
                document.body.classList.remove('modal-open');
            }
        },
    };

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-modal-open]');
        if (opener) {
            window.SigapModal.open(opener.dataset.modalOpen);
        }

        const closer = event.target.closest('[data-modal-close]');
        if (closer) {
            const target = closer.dataset.modalClose || closer.closest('.modal-backdrop')?.id;
            if (target) window.SigapModal.close(target);
        }

        if (event.target.classList?.contains('modal-backdrop') && event.target.id) {
            window.SigapModal.close(event.target.id);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('.modal-backdrop.show').forEach((modal) => {
            window.SigapModal.close(modal.id);
        });
        document.querySelectorAll('.drawer-backdrop.show').forEach((modal) => {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('drawer-open', 'modal-open');
        });
    });
</script>
</body>
</html>
