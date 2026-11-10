@extends('layouts.guru')

@section('title', 'Nilai Siswa')

@section('content')
    @php
        $nilaiRataRata = count($nilai) > 0 ? collect($nilai)->avg('akhir') : 0;
        $jumlahRemedial = collect($nilai)->filter(fn ($row) => $row['akhir'] < 75)->count();
    @endphp

    <div class="guru-page-heading guru-grade-page-heading">
        <div>
            <span class="guru-page-kicker">PENGELOLAAN AKADEMIK</span>
            <h2>Nilai Siswa</h2>
            <p>Kelola nilai siswa berdasarkan mata pelajaran dan kelas yang Anda ajar.</p>
        </div>
        <div class="guru-grade-page-actions no-print">
            <button class="btn btn-outline" type="button" data-grade-view-toggle data-grade-entry-label="Lihat Rapor" data-grade-preview-label="Kembali ke Input Nilai" aria-controls="grade-report-preview" aria-pressed="false">
                <x-icon name="file" class="button-icon" />
                <span data-grade-toggle-label>Lihat Rapor</span>
            </button>
            <form method="POST" action="{{ route('guru.nilai.impor') }}" enctype="multipart/form-data" class="guru-grade-import-form">
                @csrf
                <input type="hidden" name="kelas" value="{{ $kelas }}">
                <input type="hidden" name="mapel" value="{{ $mapel }}">
                <label class="btn btn-primary" for="grade-import-file">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5"/></svg>
                    Impor Nilai (Excel)
                </label>
                <input id="grade-import-file" name="file" type="file" accept=".xlsx,.csv" required>
            </form>
            <a class="btn btn-hijau" href="{{ route('guru.nilai.ekspor', ['kelas' => $kelas, 'mapel' => $mapel]) }}">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 4.5h10l5 5v10H4z"/><path d="M8 4.5v6h7v-6M8 19v-5h8v5M14 4.5v5h5"/></svg>
                Ekspor Rekap
            </a>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-merah">{{ $errors->first() }}</div>
    @endif

    <div class="card guru-page-card guru-grade-page-card">
        <form method="GET" action="{{ route('guru.nilai') }}" class="guru-filter-panel grade-filter-panel">
            <div class="form-group">
                <label class="form-label" for="mapel">Mata Pelajaran</label>
                <select class="form-select" id="mapel" name="mapel" onchange="this.form.submit()">
                    @foreach ($mapelTersedia as $pilihanMapel)
                        <option value="{{ $pilihanMapel }}" @selected($mapel === $pilihanMapel)>{{ $pilihanMapel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="kelas">Kelas</label>
                <select class="form-select" id="kelas" name="kelas" onchange="this.form.submit()">
                    @foreach ($kelasTersedia as $pilihanKelas)
                        <option value="{{ $pilihanKelas }}" @selected($kelas === $pilihanKelas)>{{ $pilihanKelas }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div data-grade-entry-panel>
            <div class="guru-grade-context">
                <span class="guru-grade-context-icon"><x-icon name="clipboard" /></span>
                <div><small>INPUT NILAI</small><strong>{{ $mapel }} <span>·</span> {{ $kelas }}</strong></div>
                <span class="guru-count-chip">{{ count($nilai) }} siswa</span>
            </div>
            <p class="guru-grade-import-hint no-print">Impor menggunakan file XLSX/CSV dengan kolom: Nama Siswa, Tugas, Kuis, UH, UTS, UAS, Catatan Rapor. File ekspor dapat digunakan sebagai template.</p>

            <form method="POST" action="{{ route('guru.nilai.store') }}" data-save-feedback-form>
                @csrf
                <input type="hidden" name="kelas" value="{{ $kelas }}">
                <input type="hidden" name="mapel" value="{{ $mapel }}">
                <div class="table-wrap">
                    <table class="data guru-data-table guru-grade-table">
                        <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Tugas</th>
                            <th>Kuis</th>
                            <th>UH</th>
                            <th>UTS</th>
                            <th>UAS</th>
                            <th>Nilai Akhir</th>
                            <th>Catatan Rapor</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($nilai as $index => $row)
                            <tr>
                                <td>{{ $row['siswa'] }}</td>
                                @php $nilaiAkhir = $row['akhir']; @endphp
                                @foreach (['tugas', 'kuis', 'uh', 'uts', 'uas'] as $komponen)
                                    <td>
                                        <input class="form-input grade-input"
                                               type="number" min="0" max="100" step="0.01"
                                               name="nilai[{{ $index }}][{{ $komponen }}]"
                                               value="{{ $row[$komponen] ?? 0 }}"
                                               data-grade-component="{{ $komponen }}"
                                               aria-label="{{ ucfirst($komponen) }} {{ $row['siswa'] }}" required>
                                    </td>
                                @endforeach
                                <td>
                                    <span class="guru-grade-result {{ $nilaiAkhir < 75 ? 'is-remedial' : 'is-passed' }}" data-grade-result>
                                        <strong>{{ $nilaiAkhir }}</strong>
                                        <small>{{ $nilaiAkhir < 75 ? 'Remedial' : 'Tuntas' }}</small>
                                    </span>
                                </td>
                                <td>
                                    <input class="form-input guru-grade-note" type="text" maxlength="500"
                                           name="nilai[{{ $index }}][catatan]"
                                           value="{{ old('nilai.'.$index.'.catatan', $row['catatan'] ?? '') }}"
                                           placeholder="Deskripsi capaian"
                                           aria-label="Catatan rapor {{ $row['siswa'] }}">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="form-actions">
                    <span class="form-hint">Nilai akhir: tugas 20%, kuis 15%, UH 15%, UTS 20%, UAS 30%.</span>
                    <button type="submit" class="btn btn-primary guru-save-button {{ session('sukses') === 'Nilai siswa berhasil disimpan.' ? 'is-saved' : '' }}" data-save-feedback-button>
                        <x-icon name="check" class="button-icon" />
                        <span data-save-feedback-label>Simpan Nilai Siswa</span>
                    </button>
                </div>
            </form>
        </div>

        <section id="grade-report-preview" class="guru-grade-preview" data-grade-preview-panel hidden aria-labelledby="grade-preview-title">
            <div class="guru-grade-preview-heading">
                <div>
                    <span class="guru-page-kicker">PRATINJAU RAPOR SISWA</span>
                    <h3 id="grade-preview-title">{{ $mapel }} <span>·</span> {{ $kelas }}</h3>
                    <p>Rekap nilai semester dan catatan capaian kompetensi.</p>
                </div>
            </div>
            <div class="guru-grade-preview-summary" aria-label="Ringkasan nilai kelas">
                <div><small>Jumlah Siswa</small><strong>{{ count($nilai) }}</strong></div>
                <div><small>Rata-rata Nilai</small><strong>{{ number_format($nilaiRataRata, 2, ',', '.') }}</strong></div>
                <div><small>Perlu Remedial</small><strong>{{ $jumlahRemedial }}</strong></div>
            </div>
            <p class="guru-grade-preview-note no-print">Pratinjau menggunakan nilai yang terakhir tersimpan. Simpan perubahan pada mode Input Nilai terlebih dahulu agar tampil di sini.</p>
            <div class="table-wrap">
                <table class="data guru-data-table guru-grade-report-table">
                    <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Nilai Akhir Semester</th>
                        <th>Status</th>
                        <th>Catatan Rapor / Capaian Kompetensi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($nilai as $row)
                        <tr>
                            <td><strong>{{ $row['siswa'] }}</strong></td>
                            <td class="guru-grade-report-score">{{ number_format((float) $row['akhir'], 2, ',', '.') }}</td>
                            <td>
                                <span class="guru-grade-report-status {{ $row['akhir'] < 75 ? 'is-remedial' : 'is-passed' }}">
                                    {{ $row['akhir'] < 75 ? 'Remedial' : 'Tuntas' }}
                                </span>
                            </td>
                            <td>{{ ($row['catatan'] ?? '') !== '' ? $row['catatan'] : 'Belum ada catatan rapor.' }}</td>
                        </tr>
                    @empty
                        <tr><td class="attendance-empty-row" colspan="4">Belum ada data nilai untuk ditampilkan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <script>
        (() => {
            const entryPanel = document.querySelector('[data-grade-entry-panel]');
            const previewPanel = document.querySelector('[data-grade-preview-panel]');
            const viewToggles = document.querySelectorAll('[data-grade-view-toggle]');

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

            viewToggles.forEach((button) => {
                button.addEventListener('click', () => {
                    const showPreview = previewPanel.hidden;
                    entryPanel.hidden = showPreview;
                    previewPanel.hidden = !showPreview;
                    viewToggles.forEach((toggle) => {
                        toggle.setAttribute('aria-pressed', String(showPreview));
                        const label = toggle.querySelector('[data-grade-toggle-label]');
                        if (label) {
                            label.textContent = showPreview
                                ? toggle.dataset.gradePreviewLabel
                                : toggle.dataset.gradeEntryLabel;
                        }
                    });
                    button.focus();
                });
            });

            document.getElementById('grade-import-file')?.addEventListener('change', (event) => {
                if (event.target.files?.length) event.target.form.submit();
            });

            const weights = { tugas: 0.2, kuis: 0.15, uh: 0.15, uts: 0.2, uas: 0.3 };
            document.querySelectorAll('.guru-grade-table tbody tr').forEach((row) => {
                const inputs = [...row.querySelectorAll('[data-grade-component]')];
                const result = row.querySelector('[data-grade-result]');
                const updateResult = () => {
                    if (!result) return;
                    const score = inputs.reduce((total, input) => total + (Number(input.value) || 0) * weights[input.dataset.gradeComponent], 0);
                    const rounded = Math.round(score * 100) / 100;
                    const remedial = rounded < 75;
                    result.classList.toggle('is-remedial', remedial);
                    result.classList.toggle('is-passed', !remedial);
                    result.querySelector('strong').textContent = Number.isInteger(rounded) ? String(rounded) : rounded.toFixed(2);
                    result.querySelector('small').textContent = remedial ? 'Remedial' : 'Tuntas';
                };
                inputs.forEach((input) => input.addEventListener('input', updateResult));
            });
        })();
    </script>
@endsection
