@extends('layouts.guru')

@section('title', 'Rapor Kelas')

@section('content')
    <div class="page-head">
        <div>
            <h2>Rapor Kelas</h2>
            <p>{{ $guru['mapel'] }} · Ringkasan nilai akhir siswa</p>
        </div>
        <button class="btn btn-outline no-print" type="button" onclick="window.print()">
            <x-icon name="file" class="button-icon" />
            Cetak Rapor
        </button>
    </div>

    <div class="card no-print">
        <form method="GET" action="{{ route('guru.rapor') }}" class="toolbar">
            <div class="form-group">
                <label class="form-label" for="kelas">Kelas</label>
                <select class="form-select" id="kelas" name="kelas" onchange="this.form.submit()">
                    @foreach ($kelasTersedia as $pilihanKelas)
                        <option value="{{ $pilihanKelas }}" @selected($kelas === $pilihanKelas)>{{ $pilihanKelas }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="card report-card">
        <div class="report-heading">
            <x-icon name="school" class="report-icon" />
            <div>
                <h3>{{ $sekolah['nama'] ?? 'SMA Pencawan' }}</h3>
                <p>Rapor nilai · {{ $kelas }} · {{ $guru['mapel'] }}</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Siswa</th>
                    <th>Tugas</th>
                    <th>Kuis</th>
                    <th>UH</th>
                    <th>UTS</th>
                    <th>UAS</th>
                    <th>Nilai Akhir</th>
                    <th>Ketuntasan</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($nilai as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['siswa'] }}</td>
                        <td>{{ $row['tugas'] }}</td>
                        <td>{{ $row['kuis'] }}</td>
                        <td>{{ $row['uh'] }}</td>
                        <td>{{ $row['uts'] }}</td>
                        <td>{{ $row['uas'] }}</td>
                                    <td><strong>{{ $row['akhir'] }}</strong></td>
                                    <td><span class="report-status {{ $row['akhir'] >= \App\Support\Sekolah::KKM ? 'tuntas' : 'belum-tuntas' }}">{{ $row['predikat'] }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="form-hint">Kriteria ketuntasan minimum (KKM): {{ \App\Support\Sekolah::KKM }}.</p>
    </div>
@endsection
