@extends('layouts.admin')

@section('title', 'Laporan')
@section('heading', 'Laporan')

@section('content')
    <div class="page-head">
        <div>
            <h2>Laporan Kehadiran</h2>
            <p>Rekapitulasi kehadiran guru berdasarkan bulan dan mata pelajaran.</p>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-merah" role="alert" style="margin-bottom:16px;">{{ $errors->first() }}</div>
    @endif

    <section class="card report-analytics" aria-labelledby="report-chart-title">
        <div class="report-section-heading">
            <div>
                <h3 id="report-chart-title">Tren Kehadiran Bulanan</h3>
                <p>Perbandingan total Hadir, Terlambat, dan Alpa pada seluruh periode laporan.</p>
            </div>
            <span class="report-period">{{ $mapel !== '' ? $mapel : 'Semua Mata Pelajaran' }}</span>
        </div>
        @if ($rekap !== [])
            <div class="report-chart-wrap">
                <canvas id="attendance-report-chart" role="img" aria-label="Grafik jumlah kehadiran guru"></canvas>
            </div>
            <noscript><p>Grafik memerlukan JavaScript. Jumlah bulan terpilih: Hadir {{ $chartMonthTotals['hadir'] }}, Terlambat {{ $chartMonthTotals['terlambat'] }}, Alpa {{ $chartMonthTotals['alpa'] }}.</p></noscript>
            <div class="report-chart-summary" aria-label="Ringkasan jumlah kehadiran bulan terpilih">
                <span><i class="report-dot report-dot-present"></i>Hadir <strong>{{ $chartMonthTotals['hadir'] }}</strong></span>
                <span><i class="report-dot report-dot-late"></i>Terlambat <strong>{{ $chartMonthTotals['terlambat'] }}</strong></span>
                <span><i class="report-dot report-dot-absent"></i>Alpa <strong>{{ $chartMonthTotals['alpa'] }}</strong></span>
            </div>
        @else
            <p class="report-empty">Tidak ada data kehadiran untuk filter yang dipilih.</p>
        @endif
    </section>

    <div class="card">
        <div class="toolbar no-print report-toolbar">
            <form method="GET" action="{{ route('admin.laporan') }}" class="report-filters">
                <label>
                    <span>Bulan</span>
                    <select class="form-select" name="bulan" aria-label="Pilih bulan laporan">
                        @foreach ($daftarBulan as $key => $rows)
                            <option value="{{ $key }}" {{ $bulan === $key ? 'selected' : '' }}>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $key)->locale('id')->translatedFormat('F Y') }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Mata Pelajaran</span>
                    <select class="form-select" name="mapel" aria-label="Filter mata pelajaran">
                        <option value="">Semua Mata Pelajaran</option>
                        @foreach ($daftarMapel as $namaMapel)
                            <option value="{{ $namaMapel }}" {{ $mapel === $namaMapel ? 'selected' : '' }}>{{ $namaMapel }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="btn btn-primary btn-sm">Terapkan Filter</button>
            </form>

            <div class="report-actions">
                <a href="{{ route('admin.laporan.ekspor', array_filter(['bulan' => $bulan, 'mapel' => $mapel])) }}" class="btn btn-hijau btn-sm">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 4.5h10l5 5v10H4z"/><path d="M8 4.5v6h7v-6M8 19v-5h8v5M14 4.5v5h5"/></svg>
                    Ekspor XLSX
                </a>
                <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v6H7z"/></svg>
                    Cetak Laporan
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data report-table">
                <thead>
                <tr>
                    <th>Guru</th>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Izin</th>
                    <th>Sakit</th>
                    <th>Alpa</th>
                    <th>Persentase</th>
                    <th>Catatan / Keterangan</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rekap as $row)
                    <tr>
                        <td>{{ $row['guru']['nama'] ?? '-' }}</td>
                        <td>{{ $row['hadir'] }}</td>
                        <td>{{ $row['terlambat'] }}</td>
                        <td>{{ $row['izin'] }}</td>
                        <td>{{ $row['sakit'] }}</td>
                        <td>{{ $row['alpa'] }}</td>
                        <td>{{ $row['persen'] }}%</td>
                        <td class="report-note-cell">
                            <form method="POST" action="{{ route('admin.laporan.catatan') }}" class="report-note-form no-print">
                                @csrf
                                <input type="hidden" name="bulan" value="{{ $bulan }}">
                                <input type="hidden" name="guruId" value="{{ $row['guruId'] }}">
                                <input type="hidden" name="mapel" value="{{ $mapel }}">
                                <label class="sr-only" for="report-note-{{ $loop->index }}">Catatan {{ $row['guru']['nama'] ?? 'guru' }}</label>
                                <input id="report-note-{{ $loop->index }}" name="catatan" type="text" maxlength="500" value="{{ $row['catatan'] }}" placeholder="Tambah catatan" class="form-input">
                                <button type="submit" class="btn btn-outline btn-sm">Simpan</button>
                            </form>
                            <span class="report-note-print">{{ $row['catatan'] !== '' ? $row['catatan'] : '—' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="report-empty">Tidak ada data untuk filter yang dipilih.</td></tr>
                @endforelse
                @if ($rekap !== [])
                    <tr class="report-total">
                        <th>Total</th>
                        <th>{{ $total['hadir'] }}</th>
                        <th>{{ $total['terlambat'] }}</th>
                        <th>{{ $total['izin'] }}</th>
                        <th>{{ $total['sakit'] }}</th>
                        <th>{{ $total['alpa'] }}</th>
                        <th></th>
                        <th></th>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>

    @if ($rekap !== [])
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            const reportChartCanvas = document.getElementById('attendance-report-chart');
            if (reportChartCanvas && window.Chart) {
                new Chart(reportChartCanvas, {
                    type: 'bar',
                    data: {
                        labels: @json($chartData['labels']),
                        datasets: [
                            { label: 'Hadir', data: @json($chartData['hadir']), backgroundColor: '#16a34a', borderRadius: 5, maxBarThickness: 42 },
                            { label: 'Terlambat', data: @json($chartData['terlambat']), backgroundColor: '#f59e0b', borderRadius: 5, maxBarThickness: 42 },
                            { label: 'Alpa', data: @json($chartData['alpa']), backgroundColor: '#e11d48', borderRadius: 5, maxBarThickness: 42 },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#e2e8f0' } },
                            x: { grid: { display: false } },
                        },
                    },
                });
            }
        </script>
    @endif
@endsection
