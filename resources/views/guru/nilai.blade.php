@extends('layouts.guru')

@section('title', 'Nilai Siswa')

@section('content')
    <div class="page-head">
        <div>
            <h2>Nilai Siswa</h2>
            <p>{{ $guru['mapel'] }} · Kelola nilai kelas</p>
        </div>
        <a class="btn btn-outline" href="{{ route('guru.rapor', ['kelas' => $kelas]) }}">
            <x-icon name="file" class="button-icon" />
            Lihat Rapor
        </a>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-merah">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <form method="GET" action="{{ route('guru.nilai') }}" class="toolbar">
            <div class="form-group">
                <label class="form-label" for="kelas">Kelas</label>
                <select class="form-select" id="kelas" name="kelas" onchange="this.form.submit()">
                    @foreach ($kelasTersedia as $pilihanKelas)
                        <option value="{{ $pilihanKelas }}" @selected($kelas === $pilihanKelas)>{{ $pilihanKelas }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <form method="POST" action="{{ route('guru.nilai.store') }}">
            @csrf
            <input type="hidden" name="kelas" value="{{ $kelas }}">
            <div class="table-wrap">
                <table class="data">
                    <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Tugas</th>
                        <th>Kuis</th>
                        <th>UH</th>
                        <th>UTS</th>
                        <th>UAS</th>
                        <th>Nilai Akhir</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($nilai as $index => $row)
                        <tr>
                            <td>{{ $row['siswa'] }}</td>
                            @foreach (['tugas', 'kuis', 'uh', 'uts', 'uas'] as $komponen)
                                <td>
                                    <input class="form-input grade-input"
                                           type="number" min="0" max="100" step="0.01"
                                           name="nilai[{{ $index }}][{{ $komponen }}]"
                                           value="{{ $row[$komponen] ?? 0 }}"
                                           aria-label="{{ ucfirst($komponen) }} {{ $row['siswa'] }}" required>
                                </td>
                            @endforeach
                            <td><strong>{{ $row['akhir'] }}</strong></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <span class="form-hint">Nilai akhir: tugas 20%, kuis 15%, UH 15%, UTS 20%, UAS 30%.</span>
                <button type="submit" class="btn btn-primary">
                    <x-icon name="check" class="button-icon" />
                    Simpan Nilai
                </button>
            </div>
        </form>
    </div>
@endsection
