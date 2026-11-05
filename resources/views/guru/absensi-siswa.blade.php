@extends('layouts.guru')

@section('title', 'Absensi Siswa')

@section('content')
    <div class="page-head">
        <div>
            <h2>Absensi Siswa</h2>
            <p>Kelola kehadiran kelas yang Anda ajar</p>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-merah">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <form method="GET" action="{{ route('guru.absensi-siswa') }}" class="toolbar">
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
            <button type="submit" class="btn btn-outline">Tampilkan</button>
        </form>

        <div class="ringkas-kehadiran" aria-label="Ringkasan absensi kelas">
            @foreach ($ringkasan as $label => $jumlah)
                <span class="pill">{{ $label }}: <strong>{{ $jumlah }}</strong></span>
            @endforeach
        </div>

        <form method="POST" action="{{ route('guru.absensi-siswa.store') }}">
            @csrf
            <input type="hidden" name="kelas" value="{{ $kelas }}">
            <input type="hidden" name="tanggal" value="{{ $tanggal }}">

            <div class="table-wrap">
                <table class="data">
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Siswa</th>
                        <th>Status Kehadiran</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($siswa as $index => $nama)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $nama }}</td>
                            <td>
                                <select class="form-select" name="status[{{ $index }}]" aria-label="Status {{ $nama }}">
                                    @foreach (['Hadir', 'Izin', 'Sakit', 'Alpa'] as $pilihanStatus)
                                        <option value="{{ $pilihanStatus }}" @selected(($statusSiswa[$nama] ?? 'Hadir') === $pilihanStatus)>
                                            {{ $pilihanStatus }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <x-icon name="check" class="button-icon" />
                    Simpan Absensi
                </button>
            </div>
        </form>
    </div>
@endsection
