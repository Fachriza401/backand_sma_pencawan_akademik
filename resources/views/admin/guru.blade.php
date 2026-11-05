@extends('layouts.admin')

@section('title', 'Data Guru')
@section('heading', 'Data Guru')

@section('content')
    <div class="page-head">
        <div>
            <h2>Data Guru</h2>
            <p>Kelola profil, akun, dan status keaktifan guru</p>
        </div>
        <div class="guru-actions">
            <button type="button" class="btn btn-primary" id="add-guru">Tambah Guru & Akun</button>
            <button type="button" class="btn btn-hijau" id="open-import-guru">
                <svg class="button-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 16V4m0 0L7 9m5-5 5 5M4 16v4h16v-4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Import Data Guru
            </button>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif

    @if ($errors->any() && ! $errors->has('file'))
        <div role="alert" class="alert alert-merah" style="margin-bottom:16px;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card">
        <div class="card-title-flex" style="margin-bottom:12px;">
            <h3 class="card-title">Daftar Guru</h3>
        </div>

        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>Mata Pelajaran</th>
                    <th>Akun</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($daftar as $guru)
                    @php
                        $guruId = $guru['id'] ?? '';
                        $akun = $akunGuru[$guruId] ?? [];
                    @endphp
                    <tr>
                        <td><img class="avatar" src="{{ media_url($guru['foto'] ?? '') ?: 'https://i.pravatar.cc/150' }}" alt="{{ $guru['nama'] ?? '-' }}"></td>
                        <td>{{ $guru['nama'] ?? '-' }}</td>
                        <td>{{ $guru['nip'] ?? '-' }}</td>
                        <td>{{ $guru['mapel'] ?? '-' }}</td>
                        <td>{{ $akun['email'] ?? 'Belum ada akun' }}</td>
                        <td>{!! badge_status($guru['status'] ?? '-') !!}</td>
                        <td>
                            <button
                                type="button"
                                class="btn btn-outline btn-sm edit-guru"
                                data-guru="{{ json_encode(array_merge($guru, ['akun' => $akun, 'fotoUrl' => media_url($guru['foto'] ?? '')]), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                            >Edit</button>
                            @if (($guru['status'] ?? '') === 'Aktif')
                                <form method="POST" action="{{ route('admin.guru.hapus') }}" style="display:inline;" data-archive-form>
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $guruId }}">
                                    <button type="button" class="btn btn-merah btn-sm" data-archive-trigger>Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center; color:var(--teks-2);">Belum ada data guru.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="drawer-backdrop" id="guru-drawer" aria-hidden="true">
        <section class="guru-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="form-guru-title">
            <div class="guru-drawer-head">
                <div>
                    <h3 id="form-guru-title">Tambah Guru dan Akun</h3>
                    <p>Profil guru dan akun login dikelola terpisah. Tentukan password awal akun secara manual.</p>
                </div>
                <button type="button" class="modal-close" data-close-guru aria-label="Tutup">×</button>
            </div>
            <form method="POST" action="{{ route('admin.guru.simpan') }}" id="form-guru" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="guru-id" name="id" value="{{ old('id') }}">
                <div class="guru-drawer-body">
                    <div class="guru-photo-field">
                        <img id="guru-foto-preview" class="guru-photo-preview" src="https://i.pravatar.cc/150" alt="Pratinjau foto guru">
                        <div class="guru-photo-copy">
                            <label class="form-label" for="foto">Foto Profil (opsional)</label>
                            <input class="form-input" id="foto" name="foto" type="file" accept="image/png,image/jpeg,image/webp">
                            <small class="form-hint" style="display:block; margin-top:5px;">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                            <label class="photo-remove is-hidden" id="hapus-foto-wrap">
                                <input type="checkbox" id="hapus-foto" name="hapus_foto" value="1">
                                <span>Hapus foto saat ini</span>
                            </label>
                            @error('foto') <small class="form-error" style="display:block;">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="nip">NIP</label>
                            <input id="nip" name="nip" class="form-input" type="text" value="{{ old('nip') }}" minlength="10" maxlength="30" required>
                            @error('nip') <small class="form-error" style="display:block;">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="nama">Nama</label>
                            <input id="nama" name="nama" class="form-input" type="text" value="{{ old('nama') }}" maxlength="120" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="mapel">Mata Pelajaran</label>
                            <input id="mapel" name="mapel" class="form-input" type="text" value="{{ old('mapel') }}" maxlength="80" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Status Profil</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="Aktif" @selected(old('status', 'Aktif') === 'Aktif')>Aktif</option>
                                <option value="Nonaktif" @selected(old('status') === 'Nonaktif')>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="username">Username Login</label>
                            <input id="username" name="username" class="form-input" type="text" value="{{ old('username') }}" minlength="3" maxlength="80" autocomplete="off" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email Login</label>
                            <input id="email" name="email" class="form-input" type="email" value="{{ old('email') }}" maxlength="120" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password-guru">Password Login</label>
                        <div class="password-field">
                            <input id="password-guru" name="password" class="form-input" type="password" minlength="8" maxlength="120" autocomplete="new-password" @required(! old('id'))>
                            <button type="button" class="password-visibility" aria-controls="password-guru" aria-label="Tampilkan password">Lihat</button>
                        </div>
                        <small class="password-help">Minimal 8 karakter. Wajib untuk akun baru; kosongkan saat edit jika password tidak ingin diganti.</small>
                        @error('password') <small class="form-error" style="display:block;">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="guru-drawer-foot">
                    <button type="button" class="btn btn-outline" id="reset-guru">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Profil & Akun</button>
                </div>
            </form>
        </section>
    </div>

    <div class="modal-backdrop" id="import-guru-modal" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="import-guru-title">
            <div class="modal-head">
                <h3 id="import-guru-title">Import Data Guru</h3>
                <button type="button" class="modal-close" data-close-import aria-label="Tutup">×</button>
            </div>
            <form method="POST" action="{{ route('admin.guru.impor') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <p>Unggah file .xlsx atau .csv (maksimal 2 MB dan 100 data). Password untuk setiap akun harus ditentukan di file.</p>
                    <p>Kolom wajib: <code>nip, nama, mapel, status, username, email, password</code>. Status harus <code>Aktif</code> atau <code>Nonaktif</code>, password minimal 8 karakter. Atur kolom NIP dan password sebagai teks.</p>
                    <p><a class="btn btn-outline btn-sm" href="{{ route('admin.guru.template') }}">Unduh template CSV</a></p>
                    <div class="form-group">
                        <label class="form-label" for="file-impor-guru">Pilih file guru</label>
                        <input class="form-input" id="file-impor-guru" type="file" name="file" accept=".xlsx,.csv" required>
                    </div>
                    @error('file') <p class="form-error import-file-error" style="display:block;">{{ $message }}</p> @enderror
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-outline" data-close-import>Batal</button>
                    <button type="submit" class="btn btn-hijau">Unggah & Import</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" id="archive-modal" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="archive-title">
            <div class="modal-head">
                <h3 id="archive-title">Arsipkan guru?</h3>
                <button type="button" class="modal-close" data-close-archive aria-label="Tutup">×</button>
            </div>
            <div class="modal-body">
                <p>Profil dan akun guru akan dinonaktifkan. Jadwal dan riwayat presensi tetap disimpan.</p>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-outline" data-close-archive>Batal</button>
                <button type="button" class="btn btn-merah" id="confirm-archive">Ya, arsipkan</button>
            </div>
        </div>
    </div>

    <script>
        const guruForm = document.getElementById('form-guru');
        const guruDrawer = document.getElementById('guru-drawer');
        const passwordInput = document.getElementById('password-guru');
        const passwordToggle = document.querySelector('.password-visibility');
        const fotoInput = document.getElementById('foto');
        const fotoPreview = document.getElementById('guru-foto-preview');
        const hapusFoto = document.getElementById('hapus-foto');
        const hapusFotoWrap = document.getElementById('hapus-foto-wrap');
        const defaultFoto = fotoPreview.src;
        const importModal = document.getElementById('import-guru-modal');
        const archiveModal = document.getElementById('archive-modal');
        let archiveForm = null;

        function openGuruDrawer() {
            guruDrawer.classList.add('show');
            guruDrawer.setAttribute('aria-hidden', 'false');
            document.body.classList.add('drawer-open');
        }

        function closeGuruDrawer() {
            guruDrawer.classList.remove('show');
            guruDrawer.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('drawer-open');
        }

        function openImportModal() {
            importModal.classList.add('show');
            importModal.setAttribute('aria-hidden', 'false');
        }

        function closeImportModal() {
            importModal.classList.remove('show');
            importModal.setAttribute('aria-hidden', 'true');
        }

        function setFotoPreview(url) {
            fotoPreview.src = url || defaultFoto;
        }

        function resetFotoField() {
            fotoInput.value = '';
            hapusFoto.checked = false;
            hapusFotoWrap.classList.add('is-hidden');
            setFotoPreview(defaultFoto);
        }

        function resetGuruForm() {
            guruForm.reset();
            document.getElementById('guru-id').value = '';
            document.getElementById('status').value = 'Aktif';
            document.getElementById('form-guru-title').textContent = 'Tambah Guru dan Akun';
            passwordInput.required = true;
            passwordInput.type = 'password';
            passwordToggle.textContent = 'Lihat';
            passwordToggle.setAttribute('aria-label', 'Tampilkan password');
            resetFotoField();
        }

        document.getElementById('add-guru').addEventListener('click', () => {
            resetGuruForm();
            openGuruDrawer();
        });
        document.getElementById('reset-guru').addEventListener('click', () => {
            resetGuruForm();
            closeGuruDrawer();
        });
        passwordToggle.addEventListener('click', () => {
            const showPassword = passwordInput.type === 'password';
            passwordInput.type = showPassword ? 'text' : 'password';
            passwordToggle.textContent = showPassword ? 'Sembunyikan' : 'Lihat';
            passwordToggle.setAttribute('aria-label', showPassword ? 'Sembunyikan password' : 'Tampilkan password');
        });
        fotoInput.addEventListener('change', () => {
            const file = fotoInput.files?.[0];
            if (!file) {
                setFotoPreview(defaultFoto);
                return;
            }
            hapusFoto.checked = false;
            setFotoPreview(URL.createObjectURL(file));
        });
        document.querySelectorAll('[data-close-guru]').forEach((button) => button.addEventListener('click', closeGuruDrawer));
        guruDrawer.addEventListener('click', (event) => {
            if (event.target === guruDrawer) closeGuruDrawer();
        });
        document.getElementById('open-import-guru').addEventListener('click', openImportModal);
        document.querySelectorAll('[data-close-import]').forEach((button) => button.addEventListener('click', closeImportModal));
        importModal.addEventListener('click', (event) => {
            if (event.target === importModal) closeImportModal();
        });

        document.querySelectorAll('.edit-guru').forEach((button) => {
            button.addEventListener('click', () => {
                const guru = JSON.parse(button.dataset.guru);
                document.getElementById('guru-id').value = guru.id || '';
                document.getElementById('nip').value = guru.nip || '';
                document.getElementById('nama').value = guru.nama || '';
                document.getElementById('mapel').value = guru.mapel || '';
                document.getElementById('status').value = guru.status || 'Aktif';
                document.getElementById('username').value = guru.akun?.user || '';
                document.getElementById('email').value = guru.akun?.email || '';
                passwordInput.value = '';
                passwordInput.type = 'password';
                passwordToggle.textContent = 'Lihat';
                passwordToggle.setAttribute('aria-label', 'Tampilkan password');
                fotoInput.value = '';
                hapusFoto.checked = false;
                setFotoPreview(guru.fotoUrl || defaultFoto);
                hapusFotoWrap.classList.toggle('is-hidden', !guru.foto);
                document.getElementById('form-guru-title').textContent = 'Edit Profil dan Akun Guru';
                passwordInput.required = !guru.akun?.user;
                openGuruDrawer();
            });
        });

        document.querySelectorAll('[data-archive-trigger]').forEach((button) => {
            button.addEventListener('click', () => {
                archiveForm = button.closest('[data-archive-form]');
                archiveModal.classList.add('show');
                archiveModal.setAttribute('aria-hidden', 'false');
            });
        });

        document.querySelectorAll('[data-close-archive]').forEach((button) => {
            button.addEventListener('click', () => {
                archiveModal.classList.remove('show');
                archiveModal.setAttribute('aria-hidden', 'true');
                archiveForm = null;
            });
        });

        document.getElementById('confirm-archive').addEventListener('click', () => archiveForm?.submit());

        @if ($errors->has('file'))
            openImportModal();
        @elseif ($errors->hasAny(['id', 'nip', 'nama', 'mapel', 'status', 'username', 'email', 'password']))
            passwordInput.required = !document.getElementById('guru-id').value;
            openGuruDrawer();
        @endif
    </script>
@endsection
