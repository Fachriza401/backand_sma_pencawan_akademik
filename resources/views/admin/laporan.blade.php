@extends('layouts.admin')

@section('title', 'Laporan')
@section('heading', 'Laporan')

@section('content')
    <div class="page-head">
        <div>
            <h2>Laporan Kehadiran</h2>
            <p>Rekapitulasi bulanan guru</p>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="toolbar">
            <select class="form-select" aria-label="Pilih bulan laporan" onchange="location='{{ route('admin.laporan') }}?bulan='+this.value">
                @foreach ($daftarBulan as $key => $rows)
                    <option value="{{ $key }}" {{ $bulan === $key ? 'selected' : '' }}>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $key)->locale('id')->translatedFormat('F Y') }}</option>
                @endforeach
            </select>
            <a href="{{ route('admin.laporan.ekspor', ['bulan' => $bulan]) }}" class="btn btn-hijau btn-sm no-print">Ekspor XLSX</a>
            <button type="button" class="btn btn-outline btn-sm no-print" onclick="window.print()">Cetak Laporan</button>
        </div>

        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr>
                    <th>Guru</th>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Izin</th>
                    <th>Sakit</th>
                    <th>Alpa</th>
                    <th>Persentase</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($rekap as $row)
                    <tr>
                        <td>{{ $row['guru']['nama'] ?? '-' }}</td>
                        <td>{{ $row['hadir'] }}</td>
                        <td>{{ $row['terlambat'] }}</td>
                        <td>{{ $row['izin'] }}</td>
                        <td>{{ $row['sakit'] }}</td>
                        <td>{{ $row['alpa'] }}</td>
                        <td>{{ $row['persen'] }}%</td>
                    </tr>
                @endforeach
                @if ($rekap !== [])
                    <tr>
                        <th style="text-align:right;">Total</th>
                        <th>{{ array_sum(array_column($rekap, 'hadir')) }}</th>
                        <th>{{ array_sum(array_column($rekap, 'terlambat')) }}</th>
                        <th>{{ array_sum(array_column($rekap, 'izin')) }}</th>
                        <th>{{ array_sum(array_column($rekap, 'sakit')) }}</th>
                        <th>{{ array_sum(array_column($rekap, 'alpa')) }}</th>
                        <th></th>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
@endsection
