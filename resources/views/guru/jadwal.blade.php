@extends('layouts.guru')

@section('title', 'Jadwal Guru')

@section('content')
    <div class="page-head">
        <div>
            <h2>Jadwal Saya</h2>
            <p>Jadwal mengajar mingguan Anda</p>
        </div>
    </div>

    <div class="card">
        <div class="card-title-flex">
            <h3 class="card-title">Jadwal Mingguan</h3>
        </div>

        <div class="jadwal-grid-wrap">
            <table class="jadwal-grid">
                <thead>
                <tr>
                    <th class="jam-col">Jam</th>
                    @foreach ($hari as $h)
                        <th>{{ $h }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($jam as $jp)
                    <tr>
                        <td class="jam-col">{{ $jp['ke'] }}<br><small>{{ $jp['mulai'] }} - {{ $jp['selesai'] }}</small></td>
                        @foreach ($hari as $h)
                            @php
                                $slot = collect($semuaJadwal)->first(function($row) use ($h, $jp) {
                                    return ($row['hari'] ?? null) === $h && ((int)($row['jamKe'] ?? 0)) === (int)$jp['ke'];
                                });
                            @endphp
                            <td>
                                @if ($slot)
                                    <div class="jadwal-slot isi">
                                        <div class="m">{{ $slot['mapel'] ?? '-' }}</div>
                                        <div class="k">{{ $slot['kelas'] ?? '-' }}</div>
                                        <div class="k">{{ $slot['ruang'] ?? '-' }}</div>
                                    </div>
                                @else
                                    <div class="jadwal-slot kosong">Kosong</div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
