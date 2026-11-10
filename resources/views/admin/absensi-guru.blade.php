@extends('layouts.admin')

@section('title', 'Absensi Guru')
@section('heading', 'Absensi Guru')

@section('content')
    <div class="page-head">
        <div>
            <h2>Absensi Guru</h2>
            <p>Pantau dan kelola kehadiran guru, termasuk pencatatan manual dan perapian data lama.</p>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-merah" role="alert" style="margin-bottom:16px;">{{ $errors->first() }}</div>
    @endif
    <div class="attendance-auto-policy" role="status">
        Alpa otomatis ditandai setelah batas masuk sekolah ({{ $batasAlpa->format('H:i') }}) untuk guru aktif tanpa presensi. Status Izin dan Sakit tetap dipertahankan.
        @if ($alpaOtomatis > 0)
            <strong>{{ $alpaOtomatis }} status Alpa baru diperbarui hari ini.</strong>
        @endif
    </div>

    <div class="stat-grid">
        <div class="stat-card"><div class="stat-icon hijau"><x-icon name="check" /></div><div class="stat-info"><div class="angka">{{ $ringkasan['hadir'] }}</div><div class="label">Hadir</div></div></div>
        <div class="stat-card"><div class="stat-icon kuning"><x-icon name="clock" /></div><div class="stat-info"><div class="angka">{{ $ringkasan['terlambat'] }}</div><div class="label">Terlambat</div></div></div>
        <div class="stat-card"><div class="stat-icon biru"><x-icon name="clipboard" /></div><div class="stat-info"><div class="angka">{{ $ringkasan['izin'] }}</div><div class="label">Izin/Sakit</div></div></div>
        <div class="stat-card"><div class="stat-icon merah"><x-icon name="alert" /></div><div class="stat-info"><div class="angka">{{ $ringkasan['alpa'] }}</div><div class="label">Alpa</div></div></div>
    </div>

    <div class="card">
        @if ($statusLamaCount > 0)
            <div class="attendance-legacy-notice" role="status">
                <div>
                    <strong>{{ $statusLamaCount }} data presensi memiliki status lama.</strong>
                    <span>Status tersebut akan diperbarui menjadi “Belum Hadir”; catatan tanggal dan waktu tetap dipertahankan.</span>
                </div>
                <form method="POST" action="{{ route('admin.absensi.rapikan-status-lama') }}" onsubmit="return confirm('Perbarui semua status lama menjadi Belum Hadir? Catatan tanggal dan waktu tidak diubah.');">
                    @csrf
                    <button class="btn btn-primary btn-sm" type="submit">Rapikan {{ $statusLamaCount }} Status</button>
                </form>
            </div>
        @endif

        <form method="GET" action="{{ route('admin.absensi') }}" class="toolbar absensi-filter">
            <input class="form-input grow" type="search" name="cari" value="{{ $cari }}" placeholder="Cari nama atau NIP guru..." aria-label="Cari guru">
            <label class="absensi-date-filter">
                <span class="sr-only">Tanggal</span>
                <input class="form-input" type="date" name="tgl" value="{{ $filterTgl }}" aria-label="Filter tanggal">
            </label>
            <select class="form-select" name="status" aria-label="Filter status" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach ($statusTersedia as $pilihan)
                    <option value="{{ $pilihan }}" @selected($status === $pilihan)>{{ $pilihan }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary btn-sm" type="submit">Terapkan</button>
            <a href="{{ route('admin.absensi') }}" class="btn btn-outline btn-sm">Reset</a>
            <a href="{{ route('admin.absensi.ekspor', array_filter([
                'tgl' => $filterTgl,
                'cari' => $cari,
                'status' => $status,
            ], fn ($nilai) => $nilai !== null && $nilai !== '')) }}" class="btn btn-hijau btn-sm">Ekspor XLSX</a>
            <button type="button" class="btn btn-primary btn-sm" id="open-manual-attendance">+ Tambah Kehadiran Manual</button>
        </form>

        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr>
                    <th>Guru</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($data as $item)
                    @php $g = App\Support\Sekolah::cariGuru($item['guruId'] ?? null) ?? []; @endphp
                    <tr data-absensi-id="{{ $item['id'] ?? '' }}">
                        <td>
                            <div class="table-person">
                                <img class="avatar avatar-sm" src="{{ media_url($g['foto'] ?? '') ?: 'https://i.pravatar.cc/80?u=' . urlencode($g['nama'] ?? 'guru') }}" alt="">
                                <span>{{ $g['nama'] ?? '-' }}</span>
                            </div>
                        </td>
                        <td>{{ $item['tgl'] ?? '-' }}</td>
                        <td>{{ $item['jam'] ?? '-' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.absensi.status') }}" class="inline-form">
                                @csrf
                                <input type="hidden" name="id" value="{{ $item['id'] ?? '' }}">
                                @php
                                    $statusBaris = $item['status'] ?? '';
                                    $kelasStatus = match ($statusBaris) {
                                        'Alpa' => 'is-alpa',
                                        'Belum Hadir' => 'is-not-arrived',
                                        'Izin', 'Sakit' => 'is-excused',
                                        default => '',
                                    };
                                @endphp
                                <select name="status" @class(['form-select', 'status-inline', $kelasStatus]) aria-label="Ubah status {{ $g['nama'] ?? 'guru' }}" onchange="this.form.submit()">
                                    @if (! in_array($item['status'] ?? null, $statusTersedia, true))
                                        <option value="Belum Hadir" selected>Belum Hadir · data lama</option>
                                    @endif
                                    @foreach ($statusTersedia as $pilihan)
                                        <option value="{{ $pilihan }}" @selected(($item['status'] ?? '') === $pilihan)>{{ $pilihan }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.absensi.hapus') }}" onsubmit="return confirm('Hapus data presensi ini? Tindakan ini tidak dapat dibatalkan.');">
                                @csrf
                                <input type="hidden" name="id" value="{{ $item['id'] ?? '' }}">
                                <button class="btn btn-merah btn-sm" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; color:var(--teks-2);">Tidak ada data presensi untuk filter yang dipilih.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($data->hasPages())
            <div class="pagination">
                <span style="margin-right:auto;">Menampilkan {{ $data->firstItem() }}-{{ $data->lastItem() }} dari {{ $data->total() }} data</span>
                @if ($data->onFirstPage())
                    <span class="disabled" aria-hidden="true">‹</span>
                @else
                    <a href="{{ $data->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>
                @endif
                @foreach ($data->getUrlRange(max(1, $data->currentPage() - 2), min($data->lastPage(), $data->currentPage() + 2)) as $halaman => $url)
                    <a href="{{ $url }}" class="{{ $data->currentPage() === $halaman ? 'active' : '' }}" @if ($data->currentPage() === $halaman) aria-current="page" @endif>{{ $halaman }}</a>
                @endforeach
                @if ($data->hasMorePages())
                    <a href="{{ $data->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>
                @else
                    <span class="disabled" aria-hidden="true">›</span>
                @endif
            </div>
        @endif
    </div>

    <div class="modal-backdrop" id="manual-attendance-modal" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="manual-attendance-title">
            <div class="modal-head">
                <div>
                    <h3 id="manual-attendance-title">Tambah Kehadiran Manual</h3>
                    <p class="modal-subtitle">Catat status presensi guru. Satu guru hanya dapat memiliki satu catatan per tanggal.</p>
                </div>
                <button type="button" class="modal-close" data-close-manual-attendance aria-label="Tutup">×</button>
            </div>
            <form method="POST" action="{{ route('admin.absensi.tambah-manual') }}" id="manual-attendance-form">
                @csrf
                <div class="modal-body modal-form-body">
                    <div class="form-group">
                        <label class="form-label" for="manual-guru">Guru</label>
                        <select class="form-select" id="manual-guru" name="guruId" required>
                            <option value="">Pilih guru aktif</option>
                            @foreach ($guruAktif as $guru)
                                <option value="{{ $guru['id'] }}" @selected(old('guruId') === ($guru['id'] ?? null))>{{ $guru['nama'] }} · {{ $guru['nip'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="manual-date">Tanggal</label>
                            <input class="form-input" id="manual-date" name="tgl" type="date" value="{{ old('tgl', $tanggalHariIni) }}" max="{{ $tanggalHariIni }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="manual-time">Waktu</label>
                            <input class="form-input" id="manual-time" name="jam" type="time" value="{{ old('jam', $jamSekarang) }}">
                            <small class="form-hint">Wajib untuk status Hadir atau Terlambat.</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="manual-status">Status Kehadiran</label>
                        <select class="form-select" id="manual-status" name="status" required>
                            @foreach (['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa'] as $pilihan)
                                <option value="{{ $pilihan }}" @selected(old('status', 'Hadir') === $pilihan)>{{ $pilihan }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-outline" data-close-manual-attendance>Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kehadiran</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const modalId = 'manual-attendance-modal';
            const modal = document.getElementById(modalId);
            const openButton = document.getElementById('open-manual-attendance');
            const manualStatus = document.getElementById('manual-status');
            const manualTime = document.getElementById('manual-time');

            function updateManualTimeRequirement() {
                const requiresTime = ['Hadir', 'Terlambat'].includes(manualStatus.value);
                manualTime.required = requiresTime;
                manualTime.disabled = !requiresTime;
                if (!requiresTime) manualTime.value = '';
            }

            openButton.addEventListener('click', () => window.SigapModal.open(modalId));
            manualStatus.addEventListener('change', updateManualTimeRequirement);
            updateManualTimeRequirement();
            document.querySelectorAll('[data-close-manual-attendance]').forEach((button) => {
                button.addEventListener('click', () => window.SigapModal.close(modalId));
            });
            modal.addEventListener('click', (event) => {
                if (event.target === modal) window.SigapModal.close(modalId);
            });

            @if ($errors->hasAny(['guruId', 'tgl', 'jam']))
                window.SigapModal.open(modalId);
            @endif
        })();
    </script>
@endsection
