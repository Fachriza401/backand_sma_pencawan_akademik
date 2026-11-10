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
        <div class="teacher-list-heading">
            <div>
                <h3 class="card-title">Daftar Guru</h3>
                <p>Temukan profil, kontak, jadwal, dan status akun guru dengan cepat.</p>
            </div>
            <span class="teacher-result-count"><strong id="teacher-visible-count">{{ count($daftar) }}</strong> guru</span>
        </div>

        <div class="teacher-filter-bar" role="search" aria-label="Cari dan filter guru">
            <label class="teacher-search-field" for="teacher-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg>
                <input id="teacher-search" type="search" placeholder="Cari nama atau NIP guru..." autocomplete="off">
            </label>
            <label class="teacher-subject-filter" for="teacher-subject-filter">
                <span class="sr-only">Filter mata pelajaran</span>
                <select id="teacher-subject-filter" class="form-select">
                    <option value="">Semua Mata Pelajaran</option>
                    @foreach ($daftarMapel as $mapel)
                        <option value="{{ $mapel }}">{{ $mapel }}</option>
                    @endforeach
                </select>
            </label>
            <button type="button" class="btn btn-outline btn-sm" id="reset-teacher-filters">Reset</button>
        </div>

        <div class="table-wrap">
            <table class="data teacher-data-table">
                <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>Mata Pelajaran</th>
                    <th>Kontak / WhatsApp</th>
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
                        $detail = $detailGuru[$guruId] ?? [];
                        $whatsapp = preg_replace('/\D+/', '', (string) ($guru['telepon'] ?? ''));
                        if (str_starts_with($whatsapp, '0')) {
                            $whatsapp = '62'.substr($whatsapp, 1);
                        }
                        $searchText = mb_strtolower(implode(' ', [
                            $guru['nama'] ?? '',
                            $guru['nip'] ?? '',
                            $guru['mapel'] ?? '',
                            $guru['telepon'] ?? '',
                            $akun['email'] ?? '',
                        ]));
                    @endphp
                    <tr data-teacher-row data-search="{{ $searchText }}" data-subject="{{ $guru['mapel'] ?? '' }}">
                        <td><img class="avatar teacher-table-avatar" src="{{ media_url($guru['foto'] ?? '') ?: 'https://i.pravatar.cc/150' }}" alt="{{ $guru['nama'] ?? '-' }}"></td>
                        <td><strong class="teacher-table-name">{{ $guru['nama'] ?? '-' }}</strong></td>
                        <td><span class="teacher-nip">{{ $guru['nip'] ?? '-' }}</span></td>
                        <td><span class="teacher-subject-badge">{{ $guru['mapel'] ?? '-' }}</span></td>
                        <td>
                            @if (! empty($guru['telepon']))
                                <a class="teacher-contact-link" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" aria-label="Hubungi {{ $guru['nama'] ?? 'guru' }} melalui WhatsApp">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.5 11.7a8.4 8.4 0 0 1-12.4 7.4L3 20l.9-4.9A8.4 8.4 0 1 1 20.5 11.7Z"/><path d="M8.2 8.1c.2-.5.4-.5.7-.5h.5c.2 0 .4.1.5.4l.7 1.6c.1.2.1.4 0 .6l-.5.6c-.2.2-.2.4 0 .6.4.7 1.1 1.3 1.8 1.7.2.1.4.1.6-.1l.6-.7c.2-.2.4-.2.6-.1l1.5.7c.3.1.4.3.4.5 0 .3-.2 1.2-.8 1.6-.5.4-1.1.5-1.7.3-1-.3-2.2-.9-3.3-1.9-1.1-1-1.8-2.2-2-3.1-.2-.7 0-1.3.4-1.8Z"/></svg>
                                    {{ $guru['telepon'] }}
                                </a>
                            @else
                                <span class="teacher-contact-empty">Belum diisi</span>
                            @endif
                        </td>
                        <td>
                            @if (! empty($akun['email']))
                                <div class="teacher-account-cell"><span class="teacher-account-badge is-created">Akun tersedia</span><span class="teacher-account-email">{{ $akun['email'] }}</span></div>
                            @else
                                <div class="teacher-account-cell"><span class="teacher-account-badge is-missing">Belum ada akun</span><button type="button" class="teacher-create-account" data-create-account data-guru="{{ json_encode(array_merge($guru, ['akun' => $akun, 'fotoUrl' => media_url($guru['foto'] ?? '')]), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}">Buat Akun</button></div>
                            @endif
                        </td>
                        <td>{!! badge_status($guru['status'] ?? '-') !!}</td>
                        <td>
                            <div class="teacher-row-actions">
                                <button type="button" class="teacher-icon-action is-detail" data-teacher-detail="{{ json_encode(array_merge($detail, ['fotoUrl' => media_url($guru['foto'] ?? '')]), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}" aria-label="Detail {{ $guru['nama'] ?? 'guru' }}" title="Lihat detail">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.4-6.5 9.5-6.5 9.5 6.5 9.5 6.5-3.4 6.5-9.5 6.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                                <button type="button" class="teacher-icon-action is-edit edit-guru" data-guru="{{ json_encode(array_merge($guru, ['akun' => $akun, 'fotoUrl' => media_url($guru['foto'] ?? '')]), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}" aria-label="Edit {{ $guru['nama'] ?? 'guru' }}" title="Edit profil">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m15 5 4 4M4 20l4.3-.9L19 8.4a2.1 2.1 0 0 0-3-3L5.3 16.1 4 20Z"/></svg>
                                </button>
                                @if (($guru['status'] ?? '') === 'Aktif')
                                    <form method="POST" action="{{ route('admin.guru.hapus') }}" data-archive-form>
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $guruId }}">
                                        <button type="button" class="teacher-icon-action is-delete" data-archive-trigger aria-label="Arsipkan {{ $guru['nama'] ?? 'guru' }}" title="Arsipkan guru">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M5.5 7l1 14h11l1-14M9 7V4h6v3"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center; color:var(--teks-2);">Belum ada data guru.</td></tr>
                @endforelse
                <tr id="teacher-no-results" hidden><td colspan="8" class="teacher-no-results">Tidak ada guru yang sesuai dengan pencarian atau filter.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-backdrop" id="teacher-detail-modal" aria-hidden="true">
        <section class="modal teacher-detail-modal" role="dialog" aria-modal="true" aria-labelledby="teacher-detail-name">
            <div class="modal-head">
                <div><span class="dashboard-panel-kicker">PROFIL GURU</span><h3 id="teacher-detail-name">Detail Guru</h3></div>
                <button type="button" class="modal-close" data-close-teacher-detail aria-label="Tutup">×</button>
            </div>
            <div class="modal-body">
                <div class="teacher-detail-profile">
                    <img id="teacher-detail-photo" src="" alt="">
                    <div><strong id="teacher-detail-subject">-</strong><span id="teacher-detail-nip">-</span><span id="teacher-detail-account">-</span></div>
                    <span id="teacher-detail-status" class="teacher-account-badge">-</span>
                </div>
                <div class="teacher-detail-contact" id="teacher-detail-contact"></div>
                <div class="teacher-detail-attendance" id="teacher-detail-attendance"></div>
                <section class="teacher-detail-section"><h4>Jadwal Mengajar</h4><div class="teacher-detail-schedule" id="teacher-detail-schedule"></div></section>
                <section class="teacher-detail-section"><h4>Presensi Terbaru</h4><div class="table-wrap"><table class="data teacher-detail-attendance-table"><thead><tr><th>Tanggal</th><th>Jam</th><th>Status</th></tr></thead><tbody id="teacher-detail-attendance-rows"></tbody></table></div></section>
            </div>
        </section>
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
                    <div class="form-group">
                        <label class="form-label" for="telepon">Nomor Telepon / WhatsApp</label>
                        <input id="telepon" name="telepon" class="form-input" type="tel" value="{{ old('telepon') }}" maxlength="24" autocomplete="tel" placeholder="Contoh: 081234567890">
                        <small class="form-hint">Opsional. Masukkan nomor aktif agar admin dapat menghubungi guru melalui WhatsApp.</small>
                        @error('telepon') <small class="form-error" style="display:block;">{{ $message }}</small> @enderror
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
                    <p>Kolom wajib: <code>nip, nama, mapel, status, username, email, password</code>. Kolom <code>telepon</code> opsional. Status harus <code>Aktif</code> atau <code>Nonaktif</code>, password minimal 8 karakter. Atur kolom NIP dan password sebagai teks.</p>
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
        const detailModal = document.getElementById('teacher-detail-modal');
        const teacherRows = [...document.querySelectorAll('[data-teacher-row]')];
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

        function closeTeacherDetail() {
            detailModal.classList.remove('show');
            detailModal.setAttribute('aria-hidden', 'true');
        }

        function addDetailText(container, text, className = '') {
            const element = document.createElement('span');
            element.textContent = text;
            if (className) element.className = className;
            container.append(element);
            return element;
        }

        function showTeacherDetail(guru) {
            document.getElementById('teacher-detail-name').textContent = guru.nama || 'Detail Guru';
            document.getElementById('teacher-detail-subject').textContent = guru.mapel || '-';
            document.getElementById('teacher-detail-nip').textContent = `NIP: ${guru.nip || '-'}`;
            document.getElementById('teacher-detail-account').textContent = guru.email ? `Akun: ${guru.email}` : 'Akun login belum dibuat';

            const photo = document.getElementById('teacher-detail-photo');
            photo.src = guru.fotoUrl || defaultFoto;
            photo.alt = `Foto ${guru.nama || 'guru'}`;

            const status = document.getElementById('teacher-detail-status');
            status.textContent = guru.status || '-';
            status.className = `teacher-account-badge ${guru.status === 'Aktif' ? 'is-created' : 'is-missing'}`;

            const contact = document.getElementById('teacher-detail-contact');
            contact.replaceChildren();
            if (guru.telepon) {
                const digits = guru.telepon.replace(/\D/g, '');
                const phone = digits.startsWith('0') ? `62${digits.slice(1)}` : digits;
                const link = document.createElement('a');
                link.href = `https://wa.me/${phone}`;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.className = 'teacher-contact-link';
                link.textContent = `WhatsApp: ${guru.telepon}`;
                contact.append(link);
            } else {
                addDetailText(contact, 'Nomor WhatsApp belum diisi');
            }

            const attendance = document.getElementById('teacher-detail-attendance');
            attendance.replaceChildren();
            Object.entries(guru.rekap || {}).forEach(([label, total]) => {
                const item = document.createElement('div');
                item.className = 'teacher-attendance-metric';
                addDetailText(item, label);
                addDetailText(item, String(total), 'teacher-attendance-total');
                attendance.append(item);
            });

            const schedule = document.getElementById('teacher-detail-schedule');
            schedule.replaceChildren();
            if (guru.jadwal?.length) {
                guru.jadwal.forEach((slot) => {
                    const item = document.createElement('div');
                    item.className = 'teacher-schedule-item';
                    addDetailText(item, `${slot.hari} · Jam ke-${slot.jamKe}`, 'teacher-schedule-time');
                    addDetailText(item, `${slot.kelas} · ${slot.mapel}`);
                    addDetailText(item, slot.ruang, 'teacher-schedule-room');
                    schedule.append(item);
                });
            } else {
                addDetailText(schedule, 'Belum ada jadwal mengajar yang tercatat.', 'teacher-detail-empty');
            }

            const attendanceRows = document.getElementById('teacher-detail-attendance-rows');
            attendanceRows.replaceChildren();
            if (guru.presensiTerbaru?.length) {
                guru.presensiTerbaru.forEach((record) => {
                    const row = document.createElement('tr');
                    [record.tanggal, record.jam, record.status].forEach((value) => {
                        const cell = document.createElement('td');
                        cell.textContent = value || '-';
                        row.append(cell);
                    });
                    attendanceRows.append(row);
                });
            } else {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 3;
                cell.className = 'teacher-detail-empty';
                cell.textContent = 'Belum ada riwayat presensi.';
                row.append(cell);
                attendanceRows.append(row);
            }

            detailModal.classList.add('show');
            detailModal.setAttribute('aria-hidden', 'false');
        }

        function applyTeacherFilters() {
            const search = document.getElementById('teacher-search').value.trim().toLocaleLowerCase();
            const subject = document.getElementById('teacher-subject-filter').value;
            let visible = 0;
            teacherRows.forEach((row) => {
                const matches = row.dataset.search.includes(search)
                    && (!subject || row.dataset.subject === subject);
                row.hidden = !matches;
                if (matches) visible++;
            });
            document.getElementById('teacher-visible-count').textContent = String(visible);
            document.getElementById('teacher-no-results').hidden = visible > 0;
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
                document.getElementById('telepon').value = guru.telepon || '';
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

        document.querySelectorAll('[data-create-account]').forEach((button) => {
            button.addEventListener('click', () => {
                const guru = JSON.parse(button.dataset.guru);
                button.closest('tr')?.querySelector('.edit-guru')?.click();
                if (!document.getElementById('guru-id').value) return;

                const username = `guru-${(guru.id || guru.nip || '').toLowerCase().replace(/[^a-z0-9]/g, '')}`;
                document.getElementById('username').value = username;
                document.getElementById('email').value = `${username}@pencawan.sch.id`;
                passwordInput.required = true;
                document.getElementById('form-guru-title').textContent = 'Buat Akun Guru';
            });
        });

        document.querySelectorAll('[data-teacher-detail]').forEach((button) => {
            button.addEventListener('click', () => showTeacherDetail(JSON.parse(button.dataset.teacherDetail)));
        });
        document.querySelectorAll('[data-close-teacher-detail]').forEach((button) => button.addEventListener('click', closeTeacherDetail));
        detailModal.addEventListener('click', (event) => {
            if (event.target === detailModal) closeTeacherDetail();
        });
        document.getElementById('teacher-search').addEventListener('input', applyTeacherFilters);
        document.getElementById('teacher-subject-filter').addEventListener('change', applyTeacherFilters);
        document.getElementById('reset-teacher-filters').addEventListener('click', () => {
            document.getElementById('teacher-search').value = '';
            document.getElementById('teacher-subject-filter').value = '';
            applyTeacherFilters();
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
        @elseif ($errors->hasAny(['id', 'nip', 'nama', 'mapel', 'telepon', 'status', 'username', 'email', 'password']))
            passwordInput.required = !document.getElementById('guru-id').value;
            openGuruDrawer();
        @endif
    </script>
@endsection
