<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0b4ecb">
    <link rel="icon" href="{{ asset('assets/logo-pencawan.png') }}" type="image/png">
    <title>Masuk · {{ $sekolah['nama'] ?? 'SMA Pencawan' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-900 antialiased">
    <main
        class="auth-page-enter relative isolate min-h-screen overflow-hidden bg-cover bg-center bg-no-repeat md:grid md:grid-cols-2 md:bg-none lg:grid-cols-[1.15fr_0.85fr]"
        style="background-image: url('{{ asset('assets/GambarSekolah.jfif') }}')"
    >
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-blue-950/55 via-blue-950/35 to-blue-950/80 md:hidden"></div>

        <div class="absolute -left-32 top-1/4 -z-10 hidden h-96 w-96 rounded-full bg-blue-500/10 blur-3xl md:block"></div>

        <section
            class="relative hidden min-h-screen flex-col justify-between overflow-hidden bg-slate-950 bg-cover bg-center p-10 lg:p-14 md:flex"
            style="background-image: url('{{ asset('assets/GambarSekolah.jfif') }}')"
            aria-label="Informasi SMA Pencawan"
        >
            <div class="absolute inset-0 bg-gradient-to-br from-blue-950/75 via-blue-950/45 to-slate-950/70"></div>
            <div class="absolute -left-32 top-1/4 h-96 w-96 rounded-full bg-blue-400/15 blur-3xl"></div>
            <div class="absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-300/15 blur-3xl"></div>

            <a href="{{ route('login') }}" class="relative z-10 inline-flex w-fit items-center gap-3 text-white">
                <span class="grid h-12 w-12 place-items-center overflow-hidden rounded-2xl bg-white/10 ring-1 ring-white/25">
                    <img src="{{ asset('assets/logo-pencawan.png') }}" alt="" class="h-full w-full object-contain">
                </span>
                <span>
                    <span class="block text-sm font-bold tracking-wide">SIGAP <span class="font-medium">· SMA Pencawan</span></span>
                    <span class="mt-0.5 block text-xs text-blue-100/80">Sistem Informasi Kehadiran &amp; Agenda Pengajar</span>
                </span>
            </a>

            <div class="relative z-10 max-w-2xl pb-3">
                <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-2 text-xs font-medium tracking-wide text-blue-50 backdrop-blur">
                    <span class="h-2 w-2 rounded-full bg-cyan-300 shadow-[0_0_12px_rgba(103,232,249,.9)]"></span>
                    PORTAL AKADEMIK SEKOLAH
                </p>
                <h1 class="max-w-xl text-4xl font-extrabold leading-[1.08] tracking-tight text-white lg:text-5xl xl:text-6xl">
                    Maju Bersama
                    <span class="mt-1 block bg-gradient-to-r from-cyan-200 to-sky-400 bg-clip-text text-transparent">Menuju Prestasi</span>
                </h1>
                <p class="mt-5 max-w-lg text-sm leading-6 text-blue-50/90 lg:text-base lg:leading-7">
                    Sistem informasi akademik untuk mendukung proses belajar, administrasi dan informasi sekolah secara lebih mudah, cepat dan terpercaya.
                </p>

                <div class="mt-7 grid max-w-xl grid-cols-4 gap-2.5">
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-2 py-3 text-center text-[11px] font-medium leading-4 text-white backdrop-blur-sm">
                        <svg class="mx-auto mb-2 h-5 w-5 text-cyan-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"></path></svg>
                        Pembelajaran<br>Lebih Mudah
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-2 py-3 text-center text-[11px] font-medium leading-4 text-white backdrop-blur-sm">
                        <svg class="mx-auto mb-2 h-5 w-5 text-cyan-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19V5m0 14h16"></path><path d="m7 15 4-4 3 2 5-6"></path></svg>
                        Informasi<br>Real-Time
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-2 py-3 text-center text-[11px] font-medium leading-4 text-white backdrop-blur-sm">
                        <svg class="mx-auto mb-2 h-5 w-5 text-cyan-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 8 4v5c0 5-3.4 8-8 9-4.6-1-8-4-8-9V7z"></path><path d="m9 12 2 2 4-4"></path></svg>
                        Aman &amp;<br>Terpercaya
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-2 py-3 text-center text-[11px] font-medium leading-4 text-white backdrop-blur-sm">
                        <svg class="mx-auto mb-2 h-5 w-5 text-cyan-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m13 2-3 8h7l-6 12 2-9H6z"></path></svg>
                        Akses<br>Kapan Saja
                    </div>
                </div>
            </div>

            <div class="relative z-10 flex items-end justify-between gap-5">
                <p class="font-serif text-sm italic leading-5 text-white/85">
                    Pendidikan Berkualitas<br>Untuk Masa Depan
                </p>
                <p class="text-right text-[10px] leading-4 text-blue-100/70">
                    © {{ date('Y') }} {{ $sekolah['nama'] ?? 'SMA Pencawan' }}<br>
                    Semua hak dilindungi.
                </p>
            </div>
        </section>

        <section class="auth-login-panel relative flex min-h-screen flex-col items-center justify-center px-5 sm:px-8 md:bg-[#f5f8ff] md:px-8 md:py-10 lg:px-12">
            <div class="absolute inset-0 hidden overflow-hidden md:block" aria-hidden="true">
                <div class="absolute -right-32 -top-28 h-96 w-96 rounded-full bg-blue-100/80 blur-3xl"></div>
                <div class="absolute -bottom-36 -left-24 h-96 w-96 rounded-full bg-sky-100/80 blur-3xl"></div>
                <div class="absolute inset-0 bg-[radial-gradient(#93c5fd_0.7px,transparent_0.7px)] bg-[size:18px_18px] opacity-25"></div>
                <svg class="absolute -right-20 top-1/4 h-[30rem] w-[30rem] rotate-12 text-blue-400 opacity-[.035]" viewBox="0 0 300 500" fill="currentColor" aria-hidden="true">
                    <path d="M183 0 50 260h91L95 500l155-292h-99L183 0Z"></path>
                </svg>
            </div>

            <a href="{{ route('login') }}" class="absolute left-5 top-6 z-10 inline-flex items-center gap-2.5 text-white md:hidden">
                <span class="grid h-10 w-10 place-items-center overflow-hidden rounded-xl bg-white/15 ring-1 ring-white/30 backdrop-blur">
                    <img src="{{ asset('assets/logo-pencawan.png') }}" alt="" class="h-full w-full object-contain">
                </span>
                <span class="text-xs font-semibold leading-4 drop-shadow">
                    SIGAP · SMA Pencawan<br>
                    <span class="font-normal text-blue-100">Sistem Informasi Kehadiran &amp; Agenda Pengajar</span>
                </span>
            </a>

            <div class="relative z-10 mb-5 w-full max-w-[430px] text-white md:hidden">
                <h1 class="text-2xl font-extrabold leading-tight tracking-tight drop-shadow">
                    Maju Bersama <span class="text-cyan-200">Menuju Prestasi</span>
                </h1>
                <p class="mt-1.5 text-xs leading-5 text-blue-50/90 drop-shadow">
                    Sistem informasi akademik sekolah dalam satu akses.
                </p>
            </div>

            <div class="auth-card-enter relative z-10 my-auto w-full max-w-[430px]">
                <div class="rounded-[1.8rem] bg-gradient-to-br from-blue-300 via-white to-sky-200 p-px shadow-[0_24px_70px_rgba(15,54,130,.18)]">
                    <div class="relative rounded-[calc(1.8rem-1px)] border border-white/80 bg-white/90 px-5 py-6 backdrop-blur-xl sm:px-8 sm:py-8 md:bg-white/95">
                        <span class="pointer-events-none absolute left-4 top-4 h-6 w-6 rounded-tl-xl border-l-2 border-t-2 border-blue-200/90"></span>
                        <span class="pointer-events-none absolute bottom-4 right-4 h-6 w-6 rounded-br-xl border-b-2 border-r-2 border-sky-200/90"></span>

                        <div class="relative z-10 mx-auto mb-4 grid h-14 w-14 place-items-center rounded-full bg-white p-1 shadow-[0_5px_20px_rgba(37,99,235,.22)] ring-1 ring-blue-100 sm:mb-5 sm:h-16 sm:w-16">
                            <img src="{{ asset('assets/logo-pencawan.png') }}" alt="" class="h-full w-full object-contain">
                        </div>

                        <header class="mb-5 text-center sm:mb-6">
                            <h2 class="text-xl font-extrabold tracking-tight text-blue-950 sm:text-2xl">Selamat Datang Kembali</h2>
                            <p class="mt-1.5 text-xs text-slate-500 sm:text-sm">Silakan masuk untuk melanjutkan ke sistem</p>
                        </header>

                        @if (session('status'))
                            <div role="status" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.submit') }}" class="space-y-3.5 sm:space-y-4" data-login-form>
                            @csrf

                            <div>
                                <label for="user" class="sr-only">Username atau email</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-blue-500">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M5 21a7 7 0 0 1 14 0"></path></svg>
                                    </span>
                                    <input
                                        id="user"
                                        name="user"
                                        type="text"
                                        value="{{ old('user') }}"
                                        placeholder="Username / Email"
                                        autocomplete="username"
                                        required
                                        class="block min-h-12 w-full rounded-xl border border-blue-100 bg-white/80 py-3 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                                    >
                                </div>
                            </div>

                            <div>
                                <label for="pass" class="sr-only">Password</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-blue-500">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 1 1 8 0v3"></path></svg>
                                    </span>
                                    <input
                                        id="pass"
                                        name="pass"
                                        type="password"
                                        placeholder="Password"
                                        autocomplete="current-password"
                                        required
                                        class="block min-h-12 w-full rounded-xl border border-blue-100 bg-white/80 py-3 pl-10 pr-11 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                                    >
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400 transition hover:text-blue-700 focus:outline-none focus-visible:text-blue-700"
                                        data-password-toggle
                                        aria-label="Tampilkan password"
                                        aria-pressed="false"
                                        aria-controls="pass"
                                    >
                                        <svg data-password-visible-icon class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        <svg data-password-hidden-icon class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a15.7 15.7 0 0 1-3.1 3.9"></path><path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10.5 10.5 0 0 0 4-.8"></path></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-3 text-xs text-slate-600">
                                <label for="remember" class="inline-flex min-h-11 cursor-pointer items-center gap-2">
                                    <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember')) class="h-4 w-4 rounded border-blue-200 text-blue-700 focus:ring-blue-500">
                                    Ingat Saya
                                </label>
                                <a href="{{ route('password.recovery.create') }}" class="inline-flex min-h-11 shrink-0 items-center font-semibold text-blue-700 transition hover:text-blue-950">Lupa Password?</a>
                            </div>

                            <button
                                type="submit"
                                data-login-submit
                                class="group inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-sky-500 via-blue-600 to-blue-800 px-5 py-3 text-sm font-bold text-white shadow-[0_8px_20px_rgba(37,99,235,.3)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(37,99,235,.36)] focus:outline-none focus:ring-4 focus:ring-blue-500/25 active:translate-y-0"
                            >
                                <svg data-login-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path></svg>
                                <span data-login-label>Masuk ke Sistem</span>
                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                            </button>
                        </form>

                        <details class="group mt-3">
                            <summary class="flex min-h-12 cursor-pointer list-none items-center justify-center gap-2 rounded-xl border border-blue-200 bg-white/70 px-4 py-2.5 text-xs font-semibold text-blue-800 transition hover:border-blue-400 hover:bg-blue-50 focus:outline-none focus:ring-4 focus:ring-blue-500/10">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"></path><path d="m22 7-10 6L2 7"></path></svg>
                                Hubungi Admin
                                <svg class="h-3.5 w-3.5 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                            </summary>
                            <div class="mt-2 grid gap-2">
                                @if (! empty($adminEmail))
                                    <a href="mailto:{{ $adminEmail }}?subject={{ rawurlencode('Bantuan akun Sistem Informasi Akademik') }}" class="flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-800 hover:bg-blue-100">
                                        Email administrator
                                    </a>
                                @endif
                                @if (! empty($adminPhone))
                                    <a href="https://wa.me/{{ $adminPhone }}?text={{ rawurlencode('Halo Admin, saya memerlukan bantuan untuk akun Sistem Informasi Akademik.') }}" target="_blank" rel="noopener noreferrer" class="flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">
                                        Hubungi melalui WhatsApp
                                    </a>
                                @endif
                                @if (empty($adminEmail) && empty($adminPhone))
                                    <p class="m-0 rounded-xl bg-slate-50 p-3 text-center text-xs text-slate-600">Kontak administrator belum dikonfigurasi. Hubungi pihak sekolah.</p>
                                @endif
                            </div>
                        </details>

                        <div class="mt-3 flex items-start gap-2 rounded-xl border border-blue-100 bg-blue-50/80 px-3 py-2.5 text-[10px] leading-4 text-blue-800 sm:text-[11px]">
                            <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 11v5"></path><path d="M12 8h.01"></path></svg>
                            Jika Anda belum memiliki akun atau mengalami kendala, silakan hubungi administrator sekolah.
                        </div>
                    </div>
                </div>
            </div>

            <footer class="relative z-10 mt-5 text-center text-[10px] text-white/75 drop-shadow md:hidden">
                <span class="block font-serif text-xs italic">Pendidikan Berkualitas Untuk Masa Depan</span>
                <span class="mt-1 block">© {{ date('Y') }} {{ $sekolah['nama'] ?? 'SMA Pencawan' }}. Semua hak dilindungi.</span>
            </footer>
        </section>
    </main>
</body>
</html>
