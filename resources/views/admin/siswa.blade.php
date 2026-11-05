@extends('layouts.admin')

@section('title', 'Data Siswa')
@section('heading', 'Data Siswa')

@section('content')
    <div class="page-head">
        <div>
            <h2>Data Siswa</h2>
            <p>Kelola identitas siswa, target kelulusan, serta kontak orang tua/wali.</p>
        </div>
        <div class="guru-actions">
            <button type="button" class="btn btn-primary" id="add-siswa">Tambah Data Siswa</button>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif

    @if ($errors->any())
        <div role="alert" class="alert alert-merah" style="margin-bottom:16px;">{{ $errors->first() }}</div>
    @endif

    <div class="stat-grid">
        <div class="stat-card"><div class="stat-icon biru"><x-icon name="users" /></div><div class="stat-info"><div class="angka">{{ $totalSiswa }}</div><div class="label">Total Siswa</div></div></div>
        <div class="stat-card"><div class="stat-icon hijau"><x-icon name="check" /></div><div class="stat-info"><div class="angka">{{ $totalAktif }}</div><div class="label">Siswa Aktif</div></div></div>
        <div class="stat-card"><div class="stat-icon kuning"><x-icon name="book" /></div><div class="stat-info"><div class="angka">{{ $totalKelas }}</div><div class="label">Kelas Terdata</div></div></div>
        <div class="stat-card"><div class="stat-icon merah"><x-icon name="graduation" /></div><div class="stat-info"><div class="angka">{{ $totalAngkatan }}</div><div class="label">Angkatan</div></div></div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('admin.siswa') }}" class="student-filter" style="margin-bottom:16px;">
            <div class="form-group grow">
                <label class="form-label" for="filter-cari">Cari siswa / wali / kontak</label>
                <input class="form-input" id="filter-cari" name="cari" type="search" value="{{ $cari }}" placeholder="Nama, NIS, NISN, atau nomor telepon">
            </div>
            <div class="form-group">
                <label class="form-label" for="filter-kelas">Kelas</label>
                <select class="form-select" id="filter-kelas" name="kelas">
                    <option value="">Semua kelas</option>
                    @foreach ($kelasOpsi as $k)
                        <option value="{{ $k }}" @selected($kelas === $k)>{{ $k }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="filter-angkatan">Angkatan</label>
                <select class="form-select" id="filter-angkatan" name="angkatan">
                    <option value="">Semua angkatan</option>
                    @foreach ($angkatanOpsi as $a)
                        <option value="{{ $a }}" @selected($angkatan === $a)>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="filter-status">Status</label>
                <select class="form-select" id="filter-status" name="status">
                    <option value="">Semua status</option>
                    @foreach (['Aktif', 'Lulus', 'Pindah', 'Nonaktif'] as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Terapkan</button>
                <a href="{{ route('admin.siswa') }}" class="btn btn-outline">Reset</a>
            </div>
        </form>

        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr>
                    <th>Siswa</th>
                    <th>Kelas / Angkatan</th>
                    <th>Tanggal Masuk → Target Lulus</th>
                    <th>Orang Tua / Wali</th>
                    <th>Kontak</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($daftar as $student)
                    <tr>
                        <td>
                            <span class="student-name">{{ $student->nama_lengkap }}</span>
                            <span class="student-sub">NIS {{ $student->nis }}{{ $student->nisn ? ' · NISN '.$student->nisn : '' }}</span>
                        </td>
                        <td>
                            <span class="student-name">{{ $student->kelas }}</span>
                            <span class="student-sub">Angkatan {{ $student->angkatan }}</span>
                        </td>
                        <td>
                            <span>{{ tgl_indo(optional($student->tanggal_masuk)->format('Y-m-d')) }}</span>
                            <span class="student-sub">Target: {{ tgl_indo(optional($student->target_tanggal_lulus)->format('Y-m-d')) }}</span>
                        </td>
                        <td>
                            <span class="student-name">{{ $student->nama_wali }}</span>
                            <span class="student-sub">{{ $student->hubungan_wali }}</span>
                        </td>
                        <td>
                            <div class="contact-list">
                                <div class="contact-line">
                                    <span class="contact-label">1</span>
                                    <span>{{ $student->telepon_utama }}</span>
                                    @if ($student->link_wa_utama)
                                        <a class="wa-link" href="{{ $student->link_wa_utama }}" target="_blank" rel="noopener">WA</a>
                                    @endif
                                </div>
                                <div class="contact-line">
                                    <span class="contact-label">2</span>
                                    <span>{{ $student->telepon_cadangan ?: '-' }}</span>
                                    @if ($student->link_wa_cadangan)
                                        <a class="wa-link" href="{{ $student->link_wa_cadangan }}" target="_blank" rel="noopener">WA</a>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{!! badge_status($student->status) !!}</td>
                        <td>
                            <div class="table-actions">
                                <button
                                    type="button"
                                    class="btn btn-outline btn-sm edit-siswa"
                                    data-siswa="{{ json_encode([
                                        'id' => $student->id,
                                        'nama_lengkap' => $student->nama_lengkap,
                                        'nis' => $student->nis,
                                        'nisn' => $student->nisn,
                                        'kelas' => $student->kelas,
                                        'angkatan' => $student->angkatan,
                                        'tanggal_masuk' => optional($student->tanggal_masuk)->format('Y-m-d'),
                                        'target_tanggal_lulus' => optional($student->target_tanggal_lulus)->format('Y-m-d'),
                                        'nama_wali' => $student->nama_wali,
                                        'hubungan_wali' => $student->hubungan_wali,
                                        'telepon_utama' => $student->telepon_utama,
                                        'telepon_cadangan' => $student->telepon_cadangan,
                                        'alamat' => $student->alamat,
                                        'status' => $student->status,
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                                >Edit</button>
                                <form method="POST" action="{{ route('admin.siswa.hapus') }}" onsubmit="return confirm('Hapus data siswa ini?');">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $student->id }}">
                                    <button type="submit" class="btn btn-merah btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; color:var(--teks-2);">Belum ada data siswa sesuai filter.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($daftar->hasPages())
            <div class="pagination">
                <a class="{{ $daftar->onFirstPage() ? 'disabled' : '' }}" href="{{ $daftar->previousPageUrl() }}">‹</a>
                <span>Halaman {{ $daftar->currentPage() }} dari {{ $daftar->lastPage() }} · {{ $daftar->total() }} siswa</span>
                <a class="{{ $daftar->hasMorePages() ? '' : 'disabled' }}" href="{{ $daftar->nextPageUrl() }}">›</a>
            </div>
        @endif
    </div>

    <div class="modal-backdrop" id="siswa-modal" aria-hidden="true">
        <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="siswa-modal-title">
            <div class="modal-head">
                <div>
                    <h3 id="siswa-modal-title">Tambah Data Siswa</h3>
                    <p class="modal-subtitle">Lengkapi identitas siswa dan dua nomor kontak orang tua/wali.</p>
                </div>
                <button type="button" class="modal-close" data-modal-close aria-label="Tutup">×</button>
            </div>

            <form method="POST" action="{{ route('admin.siswa.simpan') }}" id="form-siswa">
                @csrf
                <input type="hidden" id="siswa-id" name="id" value="{{ old('id') }}">
                <div class="modal-body modal-form-body modal-scroll">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="siswa-nama">Nama Lengkap</label>
                            <input class="form-input" id="siswa-nama" name="nama_lengkap" type="text" maxlength="120" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="siswa-nis">NIS</label>
                            <input class="form-input" id="siswa-nis" name="nis" type="text" maxlength="30" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="siswa-nisn">NISN</label>
                            <input class="form-input" id="siswa-nisn" name="nisn" type="text" maxlength="30">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="siswa-status">Status</label>
                            <select class="form-select" id="siswa-status" name="status" required>
                                <option value="Aktif">Aktif</option>
                                <option value="Lulus">Lulus</option>
                                <option value="Pindah">Pindah</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="siswa-kelas">Kelas</label>
                            <input class="form-input" id="siswa-kelas" name="kelas" type="text" list="siswa-kelas-list" maxlength="30" required>
                            <datalist id="siswa-kelas-list">
                                @foreach ($kelasOpsi as $k)
                                    <option value="{{ $k }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="siswa-angkatan">Angkatan</label>
                            <input class="form-input" id="siswa-angkatan" name="angkatan" type="text" maxlength="10" placeholder="Contoh: 2024" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="siswa-masuk">Tanggal Masuk</label>
                            <input class="form-input" id="siswa-masuk" name="tanggal_masuk" type="date" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="siswa-lulus">Target Tanggal Lulus</label>
                            <input class="form-input" id="siswa-lulus" name="target_tanggal_lulus" type="date">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="siswa-wali">Nama Orang Tua / Wali</label>
                            <input class="form-input" id="siswa-wali" name="nama_wali" type="text" maxlength="120" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="siswa-hubungan">Hubungan</label>
                            <select class="form-select" id="siswa-hubungan" name="hubungan_wali" required>
                                <option value="Ayah">Ayah</option>
                                <option value="Ibu">Ibu</option>
                                <option value="Wali">Wali</option>
                                <option value="Orang Tua">Orang Tua</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="siswa-telp1">Kontak 1 — Utama (WhatsApp)</label>
                            <input class="form-input" id="siswa-telp1" name="telepon_utama" type="tel" maxlength="30" placeholder="08xxxxxxxxxx" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="siswa-telp2">Kontak 2 — Cadangan</label>
                            <input class="form-input" id="siswa-telp2" name="telepon_cadangan" type="tel" maxlength="30" placeholder="Nomor alternatif jika kontak 1 tidak aktif">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" for="siswa-alamat">Alamat (opsional)</label>
                        <textarea class="form-input" id="siswa-alamat" name="alamat" rows="2" maxlength="500"></textarea>
                    </div>
                </div>
            </form>

            <div class="modal-foot">
                <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                <button type="submit" form="form-siswa" class="btn btn-primary">Simpan Data Siswa</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const title = document.getElementById('siswa-modal-title');
            const id = document.getElementById('siswa-id');
            const nama = document.getElementById('siswa-nama');
            const nis = document.getElementById('siswa-nis');
            const nisn = document.getElementById('siswa-nisn');
            const kelas = document.getElementById('siswa-kelas');
            const angkatan = document.getElementById('siswa-angkatan');
            const masuk = document.getElementById('siswa-masuk');
            const lulus = document.getElementById('siswa-lulus');
            const wali = document.getElementById('siswa-wali');
            const hubungan = document.getElementById('siswa-hubungan');
            const telp1 = document.getElementById('siswa-telp1');
            const telp2 = document.getElementById('siswa-telp2');
            const alamat = document.getElementById('siswa-alamat');
            const status = document.getElementById('siswa-status');

            const defaultData = {
                id: '',
                nama_lengkap: @json(old('nama_lengkap', '')),
                nis: @json(old('nis', '')),
                nisn: @json(old('nisn', '')),
                kelas: @json(old('kelas', $kelas ?: '')),
                angkatan: @json(old('angkatan', '')),
                tanggal_masuk: @json(old('tanggal_masuk', '')),
                target_tanggal_lulus: @json(old('target_tanggal_lulus', '')),
                nama_wali: @json(old('nama_wali', '')),
                hubungan_wali: @json(old('hubungan_wali', 'Orang Tua')),
                telepon_utama: @json(old('telepon_utama', '')),
                telepon_cadangan: @json(old('telepon_cadangan', '')),
                alamat: @json(old('alamat', '')),
                status: @json(old('status', 'Aktif')),
            };

            function openSiswa(data) {
                const merged = { ...defaultData, ...data };
                id.value = merged.id ?? '';
                nama.value = merged.nama_lengkap || '';
                nis.value = merged.nis || '';
                nisn.value = merged.nisn || '';
                kelas.value = merged.kelas || '';
                angkatan.value = merged.angkatan || '';
                masuk.value = merged.tanggal_masuk || '';
                lulus.value = merged.target_tanggal_lulus || '';
                wali.value = merged.nama_wali || '';
                hubungan.value = merged.hubungan_wali || 'Orang Tua';
                telp1.value = merged.telepon_utama || '';
                telp2.value = merged.telepon_cadangan || '';
                alamat.value = merged.alamat || '';
                status.value = merged.status || 'Aktif';

                const isEdit = merged.id !== '' && merged.id !== null && merged.id !== undefined;
                title.textContent = isEdit ? 'Edit Data Siswa' : 'Tambah Data Siswa';
                window.SigapModal.open('siswa-modal');
                setTimeout(() => nama.focus(), 120);
            }

            document.getElementById('add-siswa').addEventListener('click', () => openSiswa(defaultData));

            document.querySelectorAll('.edit-siswa').forEach((button) => {
                button.addEventListener('click', () => {
                    try {
                        openSiswa(JSON.parse(button.dataset.siswa));
                    } catch (error) {
                        openSiswa({});
                    }
                });
            });

            @if ($errors->hasAny(['id', 'nama_lengkap', 'nis', 'nisn', 'kelas', 'angkatan', 'tanggal_masuk', 'target_tanggal_lulus', 'nama_wali', 'hubungan_wali', 'telepon_utama', 'telepon_cadangan', 'alamat', 'status']))
                openSiswa({
                    id: @json(old('id', '')),
                    nama_lengkap: @json(old('nama_lengkap', '')),
                    nis: @json(old('nis', '')),
                    nisn: @json(old('nisn', '')),
                    kelas: @json(old('kelas', '')),
                    angkatan: @json(old('angkatan', '')),
                    tanggal_masuk: @json(old('tanggal_masuk', '')),
                    target_tanggal_lulus: @json(old('target_tanggal_lulus', '')),
                    nama_wali: @json(old('nama_wali', '')),
                    hubungan_wali: @json(old('hubungan_wali', 'Orang Tua')),
                    telepon_utama: @json(old('telepon_utama', '')),
                    telepon_cadangan: @json(old('telepon_cadangan', '')),
                    alamat: @json(old('alamat', '')),
                    status: @json(old('status', 'Aktif')),
                });
            @endif
        })();
    </script>
@endsection
