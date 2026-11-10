<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#082f78">
    <link rel="icon" href="{{ asset('assets/logo-pencawan.png') }}" type="image/png">
    <title>Pemulihan Akun · {{ $sekolah['nama'] ?? 'SMA Pencawan' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[radial-gradient(ellipse_at_top_right,#dbeafe,transparent_45%),linear-gradient(135deg,#f8fbff,#eaf2ff)] font-sans text-slate-900 antialiased">
    <main class="auth-page-enter flex min-h-screen items-center justify-center px-4 py-8 sm:px-6">
        <section class="auth-card-enter w-full max-w-lg rounded-[1.75rem] border border-white/80 bg-white/90 p-5 shadow-[0_24px_70px_rgba(15,54,130,.16)] backdrop-blur-xl sm:p-8">
            <a href="{{ route('login') }}" class="mb-6 inline-flex items-center gap-3">
                <img src="{{ asset('assets/logo-pencawan.png') }}" alt="" class="h-11 w-11 object-contain shadow-sm">
                <span class="text-sm font-bold text-blue-950">SIAKAD · SMA Pencawan<span class="block text-xs font-normal text-slate-500">Sistem Informasi Akademik</span></span>
            </a>

            @if ($mode === 'request')
                <h1 class="text-2xl font-extrabold tracking-tight text-blue-950">Pemulihan Akun</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">Ajukan permintaan reset password. Administrator akan memeriksa identitas Anda sebelum menyetujui.</p>

                @if ($errors->any())
                    <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.recovery.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="identifier" class="mb-1.5 block text-sm font-semibold text-slate-700">Username atau email akun</label>
                        <input id="identifier" name="identifier" value="{{ old('identifier') }}" autocomplete="username" required maxlength="120" class="min-h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                    </div>
                    <div>
                        <label for="verification" class="mb-1.5 block text-sm font-semibold text-slate-700">Informasi untuk verifikasi</label>
                        <input id="verification" name="verification" value="{{ old('verification') }}" required maxlength="120" placeholder="Email terdaftar atau NIP" class="min-h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                        <p class="mt-1.5 text-xs leading-5 text-slate-500">Masukkan email terdaftar atau NIP agar admin dapat mencocokkan identitas Anda.</p>
                    </div>
                    <button type="submit" class="min-h-12 w-full rounded-xl bg-gradient-to-r from-blue-600 to-blue-900 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-700/20 transition hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-blue-500/20">Kirim Permintaan</button>
                </form>
            @elseif ($mode === 'status')
                @php
                    $statusLabels = [
                        'pending' => 'Menunggu verifikasi administrator',
                        'approved' => 'Disetujui',
                        'rejected' => 'Tidak disetujui',
                        'expired' => 'Kedaluwarsa',
                        'completed' => 'Password berhasil diperbarui',
                    ];
                    $statusMessages = [
                        'pending' => 'Administrator sedang memeriksa informasi yang Anda kirim. Catat nomor referensi ini untuk melihat status kembali.',
                        'approved' => 'Permintaan disetujui. Minta kode pemulihan satu kali kepada administrator melalui kontak resmi sekolah.',
                        'rejected' => 'Administrator belum dapat memverifikasi permintaan ini. Hubungi admin melalui kontak resmi sekolah.',
                        'expired' => 'Kode pemulihan sudah kedaluwarsa atau terlalu banyak percobaan. Silakan buat permintaan baru.',
                        'completed' => 'Password akun sudah diperbarui. Anda dapat masuk dengan password baru.',
                    ];
                    $status = $recovery['status'] ?? 'pending';
                @endphp
                <h1 class="text-2xl font-extrabold tracking-tight text-blue-950">Status Permintaan</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">Referensi: <span class="break-all font-mono font-semibold text-blue-800">{{ $recovery['id'] }}</span></p>
                <div role="status" class="mt-5 rounded-2xl border border-blue-100 bg-blue-50/80 p-4">
                    <p class="m-0 text-sm font-bold text-blue-950">{{ $statusLabels[$status] ?? 'Status sedang ditinjau' }}</p>
                    <p class="mb-0 mt-2 text-sm leading-6 text-slate-700">{{ $statusMessages[$status] ?? 'Hubungi administrator sekolah untuk informasi lebih lanjut.' }}</p>
                </div>
                @if ($status === 'approved')
                    <a href="{{ route('password.recovery.reset', ['reference' => $recovery['id']]) }}" class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-gradient-to-r from-blue-600 to-blue-900 px-4 py-3 text-sm font-bold text-white">Masukkan Kode Pemulihan</a>
                @elseif (in_array($status, ['rejected', 'expired'], true))
                    <a href="{{ route('password.recovery.create') }}" class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-blue-200 px-4 py-3 text-sm font-bold text-blue-800">Ajukan Permintaan Baru</a>
                @endif
            @else
                <h1 class="text-2xl font-extrabold tracking-tight text-blue-950">Atur Password Baru</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">Masukkan kode satu kali yang diberikan administrator setelah identitas Anda diverifikasi.</p>

                @if ($errors->any())
                    <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.recovery.update', ['reference' => $reference]) }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="code" class="mb-1.5 block text-sm font-semibold text-slate-700">Kode pemulihan</label>
                        <input id="code" name="code" autocomplete="one-time-code" required maxlength="64" class="min-h-12 w-full rounded-xl border border-slate-200 bg-white px-4 font-mono text-sm uppercase tracking-widest outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                    </div>
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Password baru</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" class="min-h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">Ulangi password baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" class="min-h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                    </div>
                    <button type="submit" class="min-h-12 w-full rounded-xl bg-gradient-to-r from-blue-600 to-blue-900 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-700/20 transition hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-blue-500/20">Simpan Password Baru</button>
                </form>
            @endif

            <a href="{{ route('login') }}" class="mt-5 inline-flex min-h-11 w-full items-center justify-center text-sm font-semibold text-blue-700 hover:text-blue-950">Kembali ke halaman masuk</a>
        </section>
    </main>
</body>
</html>
