@extends('layouts.guru')

@section('title', 'Absensi Siswa')

@section('content')
    <div class="guru-page-heading">
        <div>
            <span class="guru-page-kicker">PENGELOLAAN KELAS</span>
            <h2>Absensi Siswa</h2>
            <p>Catat kehadiran siswa dan pantau rekap absensi kelas.</p>
        </div>
        <div class="guru-page-heading-mark"><x-icon name="check" /></div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-merah">{{ $errors->first() }}</div>
    @endif

    <div class="card guru-page-card">
        <form method="GET" action="{{ route('guru.absensi-siswa') }}" class="guru-filter-panel">
            <div class="form-group">
                <label class="form-label" for="kelas">Kelas</label>
                <select class="form-select" id="kelas" name="kelas">
                    @foreach ($kelasTersedia as $pilihanKelas)
                        <option value="{{ $pilihanKelas }}" @selected($kelas === $pilihanKelas)>{{ $pilihanKelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="tanggal">Tanggal</label>
                <input class="form-input" id="tanggal" name="tanggal" type="date" max="{{ date('Y-m-d') }}" value="{{ $tanggal }}">
            </div>
            <button type="submit" class="btn btn-primary"><x-icon name="check" class="button-icon" />Tampilkan Data</button>
        </form>

        <div class="guru-attendance-summary" aria-label="Ringkasan absensi kelas">
            @foreach ($ringkasan as $label => $jumlah)
                @php $summaryClass = ['Hadir' => 'present', 'Izin' => 'permission', 'Sakit' => 'sick', 'Alpa' => 'absent'][$label] ?? 'present'; @endphp
                <div class="guru-summary-card {{ $summaryClass }}">
                    <span>{{ $label }}</span>
                    <strong>{{ $jumlah }}</strong>
                    <small>siswa</small>
                </div>
            @endforeach
        </div>

        <div class="guru-attendance-tabs" role="tablist" aria-label="Absensi dan data siswa">
            <button type="button" class="active" id="attendance-tab-button" role="tab" aria-selected="true" aria-controls="attendance-panel">Absensi Kelas</button>
            <button type="button" id="students-tab-button" role="tab" aria-selected="false" aria-controls="students-panel">Data Siswa <span>{{ $jumlahSiswa }}</span></button>
        </div>

        <section id="attendance-panel" role="tabpanel" aria-labelledby="attendance-tab-button">
        <form method="POST" action="{{ route('guru.absensi-siswa.store') }}" class="guru-entry-form" data-save-feedback-form>
            @csrf
            <input type="hidden" name="kelas" value="{{ $kelas }}">
            <input type="hidden" name="tanggal" value="{{ $tanggal }}">

            <div class="guru-attendance-table-heading">
                <div>
                    <h3>Daftar Kehadiran</h3>
                    <p>Pilih status setiap siswa. Catatan tersedia untuk Izin, Terlambat, atau Sakit.</p>
                </div>
                <button type="button" class="btn btn-sm guru-mark-all-present" data-mark-all-present>
                    <x-icon name="check" class="button-icon" /> <strong>Tandai Semua Hadir</strong>
                </button>
            </div>
            <div class="table-wrap">
                <table class="data guru-data-table guru-attendance-table">
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Siswa</th>
                        <th>Status Kehadiran</th>
                        <th>Catatan / Keterangan</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($namaSiswa as $index => $nama)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $nama }}</td>
                            <td>
                                <select class="form-select guru-attendance-select status-{{ strtolower($statusSiswa[$nama] ?? 'hadir') }}" name="status[{{ $index }}]" aria-label="Status {{ $nama }}">
                                    @foreach (['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa'] as $pilihanStatus)
                                        <option value="{{ $pilihanStatus }}" @selected(($statusSiswa[$nama] ?? 'Hadir') === $pilihanStatus)>
                                            {{ $pilihanStatus }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                @php
                                    $statusSaatIni = old('status.'.$index, $statusSiswa[$nama] ?? 'Hadir');
                                    $catatanAktif = in_array($statusSaatIni, ['Izin', 'Terlambat', 'Sakit'], true);
                                @endphp
                                <div class="guru-attendance-note-wrap" data-attendance-note-wrap @if (! $catatanAktif) hidden @endif>
                                    <input class="form-input guru-attendance-note" type="text" name="catatan[{{ $index }}]" maxlength="240"
                                           value="{{ old('catatan.'.$index, $catatanSiswa[$nama] ?? '') }}"
                                           placeholder="Alasan / keterangan"
                                           aria-label="Catatan {{ $nama }}"
                                           @readonly(! $catatanAktif)>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @if ($namaSiswa === [])
                        <tr><td colspan="4" class="attendance-empty-row">Belum ada siswa aktif pada kelas ini.</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary guru-save-button {{ session('sukses') === 'Absensi siswa berhasil disimpan.' ? 'is-saved' : '' }}" data-save-feedback-button>
                    <x-icon name="check" class="button-icon" />
                    <span data-save-feedback-label>Simpan Absensi Kelas</span>
                </button>
            </div>
        </form>
        </section>

        <section id="students-panel" role="tabpanel" aria-labelledby="students-tab-button" hidden>
            <div class="guru-student-panel-heading">
                <div>
                    <h3>Data Siswa {{ $kelas }}</h3>
                    <p>Data pada kelas ini saja. Perubahan juga digunakan untuk absensi dan nilai.</p>
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="new-student-button"><x-icon name="users" class="button-icon" />Tambah Siswa</button>
            </div>

            <div class="table-wrap">
                <table class="data guru-data-table guru-student-table">
                    <thead><tr><th>Siswa</th><th>Orang Tua / Wali</th><th>Kontak</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse ($siswa as $student)
                        <tr>
                            <td><strong>{{ $student->nama_lengkap }}</strong><small>NIS {{ $student->nis }} · {{ $student->kelas }}</small></td>
                            <td><strong>{{ $student->nama_wali }}</strong><small>{{ $student->hubungan_wali }}</small></td>
                            <td><strong>{{ $student->telepon_utama }}</strong><small>Cadangan: {{ $student->telepon_cadangan ?: '-' }}</small></td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="btn btn-outline btn-sm edit-student"
                                        data-student="{{ json_encode([
                                            'id' => $student->id,
                                            'nama_lengkap' => $student->nama_lengkap,
                                            'nis' => $student->nis,
                                            'nisn' => $student->nisn,
                                            'angkatan' => $student->angkatan,
                                            'nama_wali' => $student->nama_wali,
                                            'hubungan_wali' => $student->hubungan_wali,
                                            'telepon_utama' => $student->telepon_utama,
                                            'telepon_cadangan' => $student->telepon_cadangan,
                                            'alamat' => $student->alamat,
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}">Edit</button>
                                    <form method="POST" action="{{ route('guru.absensi-siswa.siswa.hapus', ['panel' => 'siswa']) }}" onsubmit="return confirm('Hapus data siswa ini? Data absensi sebelumnya tetap tersimpan.');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="id" value="{{ $student->id }}">
                                        <input type="hidden" name="kelas" value="{{ $kelas }}">
                                        <input type="hidden" name="panel" value="siswa">
                                        <button type="submit" class="btn btn-merah btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="attendance-empty-row">Belum ada siswa aktif pada kelas ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="modal-backdrop" id="student-form-modal" aria-hidden="true">
        <section class="modal guru-student-modal" role="dialog" aria-modal="true" aria-labelledby="student-form-title">
            <div class="modal-head">
                <div>
                    <h3 id="student-form-title">Tambah siswa ke kelas {{ $kelas }}</h3>
                    <p class="modal-subtitle">Lengkapi identitas dan informasi orang tua/wali siswa.</p>
                </div>
                <button type="button" class="modal-close" data-student-modal-close aria-label="Tutup">×</button>
            </div>
            <form method="POST" action="{{ route('guru.absensi-siswa.siswa.simpan', ['panel' => 'siswa']) }}" class="guru-student-form" id="student-form">
                @csrf
                <input type="hidden" name="id" id="student-id">
                <input type="hidden" name="kelas" value="{{ $kelas }}">
                <input type="hidden" name="panel" value="siswa">
                <div class="modal-body modal-scroll">
                    <div class="guru-student-form-grid">
                        <label>Nama lengkap<input class="form-input" name="nama_lengkap" id="student-name" value="{{ old('nama_lengkap') }}" required maxlength="120"></label>
                        <label>NIS<input class="form-input" name="nis" id="student-nis" value="{{ old('nis') }}" required maxlength="30"></label>
                        <label>NISN<input class="form-input" name="nisn" id="student-nisn" value="{{ old('nisn') }}" maxlength="30"></label>
                        <label>Angkatan<input class="form-input" name="angkatan" id="student-cohort" required maxlength="10" value="{{ old('angkatan', date('Y')) }}"></label>
                        <label>Nama orang tua/wali<input class="form-input" name="nama_wali" id="student-guardian" value="{{ old('nama_wali') }}" required maxlength="120"></label>
                        <label>Pilihan orang tua/wali
                            <select class="form-select" name="hubungan_wali" id="student-relationship" required>
                                <option @selected(old('hubungan_wali', 'Ayah') === 'Ayah')>Ayah</option>
                                <option @selected(old('hubungan_wali') === 'Ibu')>Ibu</option>
                                <option @selected(old('hubungan_wali') === 'Wali')>Wali</option>
                                <option @selected(old('hubungan_wali') === 'Orang Tua')>Orang Tua</option>
                            </select>
                        </label>
                        <label>Nomor kontak utama<input class="form-input" name="telepon_utama" id="student-phone" value="{{ old('telepon_utama') }}" required maxlength="30" type="tel"></label>
                        <label>Nomor kontak cadangan<input class="form-input" name="telepon_cadangan" id="student-phone-alt" value="{{ old('telepon_cadangan') }}" maxlength="30" type="tel"></label>
                        <label class="guru-student-address">Alamat<textarea class="form-input" name="alamat" id="student-address" rows="2" maxlength="500">{{ old('alamat') }}</textarea></label>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-outline" id="cancel-student-edit">Batal</button>
                    <button type="submit" class="btn btn-primary" id="student-form-submit"><x-icon name="check" class="button-icon" /><span data-student-submit-label>Simpan Siswa Baru</span></button>
                </div>
            </form>
        </section>
    </div>
    <script>
        (() => {
            const attendanceButton = document.getElementById('attendance-tab-button');
            const studentsButton = document.getElementById('students-tab-button');
            const attendancePanel = document.getElementById('attendance-panel');
            const studentsPanel = document.getElementById('students-panel');
            const form = document.getElementById('student-form');
            const studentModal = document.getElementById('student-form-modal');
            const startNewButton = document.getElementById('new-student-button');
            const closeFormButton = document.getElementById('cancel-student-edit');
            const closeModalButton = studentModal.querySelector('[data-student-modal-close]');
            const submitStudentButton = document.getElementById('student-form-submit');
            const submitStudentLabel = submitStudentButton.querySelector('[data-student-submit-label]');
            const markAllPresentButton = document.querySelector('[data-mark-all-present]');
            const attendanceSelects = [...document.querySelectorAll('.guru-attendance-select')];
            let modalTrigger = null;

            document.querySelectorAll('[data-save-feedback-form]').forEach((saveForm) => {
                saveForm.addEventListener('submit', (event) => {
                    if (!saveForm.reportValidity()) {
                        event.preventDefault();
                        return;
                    }

                    const button = saveForm.querySelector('[data-save-feedback-button]');
                    if (!button) return;
                    button.classList.add('is-saving');
                    button.setAttribute('aria-busy', 'true');
                    button.querySelector('[data-save-feedback-label]').textContent = 'Menyimpan...';
                });
            });

            const openStudentModal = (trigger) => {
                modalTrigger = trigger;
                studentModal.classList.add('show');
                studentModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                document.getElementById('student-name').focus();
            };
            const closeStudentModal = () => {
                studentModal.classList.remove('show');
                studentModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
                modalTrigger?.focus();
            };

            const updateAttendanceSelect = (select) => {
                const status = select.value.toLowerCase();
                select.classList.remove('status-hadir', 'status-terlambat', 'status-izin', 'status-sakit', 'status-alpa');
                select.classList.add(`status-${status}`);
                const note = select.closest('tr')?.querySelector('.guru-attendance-note');
                const noteWrap = select.closest('tr')?.querySelector('[data-attendance-note-wrap]');
                const noteIsActive = ['izin', 'terlambat', 'sakit'].includes(status);
                if (note) {
                    note.readOnly = !noteIsActive;
                    note.classList.toggle('is-readonly', note.readOnly);
                    if (!noteIsActive) note.value = '';
                }
                if (noteWrap) noteWrap.hidden = !noteIsActive;
            };

            attendanceSelects.forEach((select) => {
                updateAttendanceSelect(select);
                select.addEventListener('change', () => updateAttendanceSelect(select));
            });
            markAllPresentButton?.addEventListener('click', () => {
                attendanceSelects.forEach((select) => {
                    select.value = 'Hadir';
                    updateAttendanceSelect(select);
                });
            });

            const showPanel = (students) => {
                attendancePanel.hidden = students;
                studentsPanel.hidden = !students;
                attendanceButton.classList.toggle('active', !students);
                studentsButton.classList.toggle('active', students);
                attendanceButton.setAttribute('aria-selected', String(!students));
                studentsButton.setAttribute('aria-selected', String(students));
            };
            const resetForm = () => {
                form.reset();
                form.querySelectorAll('input:not([type="hidden"]), textarea').forEach((input) => {
                    input.value = '';
                });
                document.getElementById('student-relationship').value = 'Ayah';
                document.getElementById('student-id').value = '';
                document.getElementById('student-cohort').value = '{{ date('Y') }}';
                document.getElementById('student-form-title').textContent = 'Tambah siswa ke kelas {{ $kelas }}';
                submitStudentLabel.textContent = 'Simpan Siswa Baru';
            };

            attendanceButton.addEventListener('click', () => showPanel(false));
            studentsButton.addEventListener('click', () => showPanel(true));
            startNewButton.addEventListener('click', () => {
                resetForm();
                document.getElementById('student-form-title').textContent = 'Tambah siswa ke kelas {{ $kelas }}';
                submitStudentLabel.textContent = 'Simpan Siswa Baru';
                openStudentModal(startNewButton);
            });
            closeFormButton.addEventListener('click', () => {
                resetForm();
                closeStudentModal();
            });
            closeModalButton.addEventListener('click', () => {
                resetForm();
                closeStudentModal();
            });
            studentModal.addEventListener('click', (event) => {
                if (event.target === studentModal) {
                    resetForm();
                    closeStudentModal();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && studentModal.classList.contains('show')) {
                    resetForm();
                    closeStudentModal();
                }
            });
            document.querySelectorAll('.edit-student').forEach((button) => {
                button.addEventListener('click', () => {
                    const student = JSON.parse(button.dataset.student);
                    document.getElementById('student-form-title').textContent = 'Edit data siswa';
                    submitStudentLabel.textContent = 'Simpan Perubahan';
                    Object.entries({
                        id: 'student-id',
                        nama_lengkap: 'student-name',
                        nis: 'student-nis',
                        nisn: 'student-nisn',
                        angkatan: 'student-cohort',
                        nama_wali: 'student-guardian',
                        hubungan_wali: 'student-relationship',
                        telepon_utama: 'student-phone',
                        telepon_cadangan: 'student-phone-alt',
                        alamat: 'student-address',
                    }).forEach(([key, elementId]) => {
                        document.getElementById(elementId).value = student[key] || '';
                    });
                    openStudentModal(button);
                });
            });

            @if ($errors->hasAny(['id', 'nama_lengkap', 'nis', 'nisn', 'angkatan', 'nama_wali', 'hubungan_wali', 'telepon_utama', 'telepon_cadangan', 'alamat']))
                showPanel(true);
                openStudentModal(startNewButton);
            @endif
            @if (request()->query('panel') === 'siswa')
                showPanel(true);
            @endif
        })();
    </script>
@endsection
