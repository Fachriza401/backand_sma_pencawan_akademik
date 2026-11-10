@extends('layouts.guru')

@section('title', 'Laporan Absensi Siswa')

@section('content')
    <div class="page-head student-report-page-head student-report-full-width">
        <div>
            <span class="guru-page-kicker">LAPORAN KEHADIRAN</span>
            <h2>Laporan Absensi Siswa</h2>
            <p>{{ $kelas }} · {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $bulan)->locale('id')->translatedFormat('F Y') }}</p>
        </div>
        <div class="student-report-actions no-print print:hidden">
            <a class="btn btn-hijau" href="{{ route('guru.rapor.ekspor', ['kelas' => $kelas, 'bulan' => $bulan]) }}">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 4.5h10l5 5v10H4z"/><path d="M8 4.5v6h7v-6M8 19v-5h8v5M14 4.5v5h5"/></svg>
                Ekspor XLSX
            </a>
            <button class="btn btn-outline" type="button" onclick="window.print()">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v6H7z"/></svg>
                Cetak Laporan
            </button>
        </div>
    </div>

    <div class="card no-print print:hidden student-report-filters student-report-full-width">
        <form method="GET" action="{{ route('guru.rapor') }}" class="guru-filter-panel grade-filter-panel">
            <div class="form-group">
                <label class="form-label" for="kelas">Kelas</label>
                <select class="form-select" id="kelas" name="kelas" onchange="this.form.submit()">
                    @foreach ($kelasTersedia as $pilihanKelas)
                        <option value="{{ $pilihanKelas }}" @selected($kelas === $pilihanKelas)>{{ $pilihanKelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="bulan">Bulan</label>
                <input class="form-input" id="bulan" name="bulan" type="month" value="{{ $bulan }}" max="{{ date('Y-m') }}" onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <section class="student-report-summary student-report-full-width" aria-label="Ringkasan absensi kelas">
        <article class="card student-report-metric">
            <span class="student-report-metric-icon metric-students"><x-icon name="users" /></span>
            <div><small>Total Siswa Aktif</small><strong>{{ $ringkasan['totalSiswa'] }}</strong><span>siswa di {{ $kelas }}</span></div>
        </article>
        <article class="card student-report-metric">
            <span class="student-report-metric-icon metric-attendance"><x-icon name="check" /></span>
            <div><small>Rata-rata Kehadiran Kelas</small><strong>{{ $ringkasan['persentaseKelas'] }}%</strong><span>dari hari tercatat</span></div>
        </article>
        <article class="card student-report-metric">
            <span class="student-report-metric-icon metric-absence"><x-icon name="alert" /></span>
            <div><small>Akumulasi Ketidakhadiran</small><strong>{{ $ringkasan['totalKetidakhadiran'] }}</strong><span>Sakit, Izin, dan Alpa</span></div>
        </article>
    </section>

    <div class="card report-card student-report-card student-report-full-width">
        <div class="report-heading">
            <div>
                <h3>{{ $sekolah['nama'] ?? 'SMA Pencawan' }}</h3>
                <p>Laporan absensi siswa · {{ $kelas }} · {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $bulan)->locale('id')->translatedFormat('F Y') }}</p>
            </div>
        </div>
        <div class="student-report-table-wrap student-report-print-area print:block print:w-full">
            <table class="data guru-data-table student-report-table">
                <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Siswa</th>
                    <th>Hadir (H)</th>
                    <th>Sakit (S)</th>
                    <th>Izin (I)</th>
                    <th>Alpa (A)</th>
                    <th>Total Kehadiran</th>
                    <th>Persentase Kehadiran (%)</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rekap as $index => $row)
                    @php
                        $badgePersentase = $row['persen'] >= 90 ? 'is-high' : ($row['persen'] >= 75 ? 'is-medium' : 'is-low');
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="student-report-name">{{ $row['siswa']->nama_lengkap }}</td>
                        <td>{{ $row['totalHadir'] }}</td>
                        <td>{{ $row['sakit'] }}</td>
                        <td>{{ $row['izin'] }}</td>
                        <td>{{ $row['alpa'] }}</td>
                        <td class="student-report-total">{{ $row['totalKehadiran'] }}</td>
                        <td><span class="student-report-percent {{ $badgePersentase }}">{{ $row['persen'] }}%</span></td>
                    </tr>
                @empty
                    <tr><td class="attendance-empty-row" colspan="8">Belum ada siswa aktif untuk kelas ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
