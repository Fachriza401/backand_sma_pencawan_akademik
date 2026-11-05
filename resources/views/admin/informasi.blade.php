@extends('layouts.admin')

@section('title', 'Manajemen Informasi')
@section('heading', 'Manajemen Informasi')

@section('content')
    <div class="page-head">
        <div>
            <h2>Manajemen Informasi</h2>
            <p>Buat pengumuman sekolah dan tentukan kapan informasi dapat dibaca guru.</p>
        </div>
    </div>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-merah" role="alert" style="margin-bottom:16px;">{{ $errors->first() }}</div>
    @endif

    <div class="card" style="margin-bottom:16px;">
        <h3 class="card-title" id="informasi-form-title">Buat Informasi Baru</h3>
        <form method="POST" action="{{ route('admin.informasi.simpan') }}" id="informasi-form">
            @csrf
            <input type="hidden" name="id" id="informasi-id" value="{{ old('id') }}">
            <div class="form-group">
                <label class="form-label" for="judul">Judul</label>
                <input class="form-input" id="judul" name="judul" maxlength="160" value="{{ old('judul') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="isi">Isi pengumuman</label>
                <textarea class="form-input" id="isi" name="isi" rows="5" maxlength="10000" required>{{ old('isi') }}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="pengirim">Pengirim (opsional)</label>
                    <input class="form-input" id="pengirim" name="pengirim" maxlength="100" value="{{ old('pengirim') }}" placeholder="Contoh: Kepala Sekolah">
                </div>
                <div class="form-group">
                    <label class="form-label" for="targetKelas">Target Kelas (opsional)</label>
                    <input class="form-input" id="targetKelas" name="targetKelas" maxlength="60" value="{{ old('targetKelas') }}" list="informasi-kelas-list" placeholder="Semua guru jika kosong">
                    <datalist id="informasi-kelas-list">
                        @foreach ($daftarKelas as $kelas)
                            <option value="{{ $kelas }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="urgensi">Urgensi</label>
                <select class="form-select" id="urgensi" name="urgensi">
                    @foreach (['Normal', 'Penting', 'Rendah'] as $urgensi)
                        <option value="{{ $urgensi }}" @selected(old('urgensi', 'Normal') === $urgensi)>{{ $urgensi }}</option>
                    @endforeach
                </select>
            </div>
            <label style="display:flex; align-items:center; gap:9px; margin-bottom:16px;">
                <input type="checkbox" name="published" value="1" @checked(old('published'))>
                <span>Publikasikan sekarang agar terlihat oleh guru</span>
            </label>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button class="btn btn-outline" id="reset-informasi" type="button">Bersihkan</button>
                <button class="btn btn-primary" type="submit">Simpan Informasi</button>
            </div>
        </form>
    </div>

    @forelse ($informasi as $item)
        <article class="card" style="margin-bottom:12px;">
            <div class="card-title-flex">
                <div>
                    <h3 class="card-title" style="margin-bottom:5px;">{{ $item['judul'] ?? 'Informasi' }}</h3>
                    <div class="info-meta">
                        {!! badge_status($item['urgensi'] ?? 'Normal') !!}
                        @if (! empty($item['pengirim']))
                            <span>Dari {{ $item['pengirim'] }}</span>
                        @endif
                        @if (! empty($item['targetKelas']))
                            <span>Untuk {{ $item['targetKelas'] }}</span>
                        @endif
                        <span>{{ ($item['published'] ?? false) ? 'Dipublikasikan' : 'Draf' }}</span>
                        <span>diperbarui {{ isset($item['updatedAt']) ? \Illuminate\Support\Carbon::parse($item['updatedAt'])->format('d/m/Y H:i') : '-' }}</span>
                    </div>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button
                        type="button"
                        class="btn btn-outline btn-sm edit-informasi"
                        data-informasi="{{ json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                    >Edit</button>
                    <form method="POST" action="{{ route('admin.informasi.hapus') }}" onsubmit="return confirm('Hapus informasi ini?');">
                        @csrf
                        <input type="hidden" name="id" value="{{ $item['id'] }}">
                        <button class="btn btn-merah btn-sm" type="submit">Hapus</button>
                    </form>
                </div>
            </div>
            <p style="white-space:pre-wrap; margin:16px 0 0; color:var(--teks-2);">{{ $item['isi'] ?? '' }}</p>
        </article>
    @empty
        <div class="card" style="color:var(--teks-2);">Belum ada informasi yang dibuat.</div>
    @endforelse

    <script>
        const infoForm = document.getElementById('informasi-form');
        const resetInfo = () => {
            infoForm.reset();
            document.getElementById('informasi-id').value = '';
            document.getElementById('informasi-form-title').textContent = 'Buat Informasi Baru';
        };
        document.getElementById('reset-informasi').addEventListener('click', resetInfo);
        document.querySelectorAll('.edit-informasi').forEach((button) => {
            button.addEventListener('click', () => {
                const item = JSON.parse(button.dataset.informasi);
                document.getElementById('informasi-id').value = item.id || '';
                document.getElementById('judul').value = item.judul || '';
                document.getElementById('isi').value = item.isi || '';
                document.getElementById('pengirim').value = item.pengirim || '';
                document.getElementById('targetKelas').value = item.targetKelas || '';
                document.getElementById('urgensi').value = item.urgensi || 'Normal';
                infoForm.querySelector('[name="published"]').checked = item.published === true;
                document.getElementById('informasi-form-title').textContent = 'Edit Informasi';
                infoForm.scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
@endsection
