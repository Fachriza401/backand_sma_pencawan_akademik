@extends('layouts.admin')

@section('title', 'Manajemen Informasi')
@section('heading', 'Manajemen Informasi')

@section('content')
    <section class="info-admin-hero">
        <div class="info-admin-hero-icon"><x-icon name="file" /></div>
        <div>
            <span class="info-admin-kicker">KOMUNIKASI SEKOLAH</span>
            <h2>Manajemen Informasi</h2>
            <p>Susun pengumuman yang jelas dan sampaikan informasi penting kepada guru.</p>
        </div>
        <div class="info-admin-count"><strong>{{ count($informasi) }}</strong><span>Informasi tersimpan</span></div>
    </section>

    @if (session('sukses'))
        <div class="alert alert-hijau" role="status" style="margin-bottom:16px;">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-merah" role="alert" style="margin-bottom:16px;">{{ $errors->first() }}</div>
    @endif

    <div class="card info-composer">
        <div class="info-composer-heading">
            <span class="info-composer-icon"><x-icon name="clipboard" /></span>
            <div>
                <h3 id="informasi-form-title">Buat Informasi Baru</h3>
                <p>Lengkapi detail pengumuman sebelum dibagikan.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.informasi.simpan') }}" id="informasi-form">
            @csrf
            <input type="hidden" name="id" id="informasi-id" value="{{ old('id') }}">
            <div class="info-form-grid">
                <div class="info-form-fields">
                    <div class="form-group">
                        <label class="form-label" for="judul">Judul informasi</label>
                        <input class="form-input" id="judul" name="judul" maxlength="160" value="{{ old('judul') }}" placeholder="Contoh: Jadwal rapat guru" required>
                    </div>
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
                    <div class="form-group">
                        <label class="form-label" for="urgensi">Tingkat urgensi</label>
                        <select class="form-select" id="urgensi" name="urgensi">
                            @foreach (['Normal', 'Penting', 'Rendah'] as $urgensi)
                                <option value="{{ $urgensi }}" @selected(old('urgensi', 'Normal') === $urgensi)>{{ $urgensi }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group info-form-message">
                    <label class="form-label" for="isi">Isi pengumuman</label>
                    <textarea class="form-input" id="isi" name="isi" rows="9" maxlength="10000" placeholder="Tuliskan informasi dengan ringkas dan jelas..." required>{{ old('isi') }}</textarea>
                </div>
            </div>
            <label class="info-publish-toggle">
                <input type="checkbox" name="published" value="1" @checked(old('published'))>
                <span><strong>Publikasikan sekarang</strong><small>Informasi langsung tersedia untuk guru.</small></span>
            </label>
            <div class="info-form-actions">
                <button class="btn btn-outline" id="reset-informasi" type="button">Bersihkan</button>
                <button class="btn btn-primary" type="submit"><x-icon name="check" /> Simpan Informasi</button>
            </div>
        </form>
    </div>

    <section class="info-list-section" aria-labelledby="info-list-title">
        <div class="info-list-heading">
            <div>
                <span class="info-admin-kicker">ARSIP PENGUMUMAN</span>
                <h3 id="info-list-title">Informasi Tersimpan</h3>
            </div>
            <span class="info-list-count">{{ count($informasi) }} informasi</span>
        </div>
        <div class="info-item-list">
            @forelse ($informasi as $item)
                @php
                $urgensi = $item['urgensi'] ?? 'Normal';
                $urgensiClass = match ($urgensi) {
                    'Penting' => 'is-important',
                    'Rendah' => 'is-low',
                    default => 'is-normal',
                };
                @endphp
                <article class="card info-item-card">
                <div class="info-item-heading">
                    <div class="info-item-title-wrap">
                        <span class="info-urgency-badge {{ $urgensiClass }}">{{ $urgensi }}</span>
                        <h4>{{ $item['judul'] ?? 'Informasi' }}</h4>
                    </div>
                    <div class="info-item-actions">
                        <button
                            type="button"
                            class="info-icon-action is-edit edit-informasi"
                            data-informasi="{{ json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                            aria-label="Edit {{ $item['judul'] ?? 'informasi' }}"
                            title="Edit informasi"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m15 5 4 4M4 20l4.3-.9L19 8.4a2.1 2.1 0 0 0-3-3L5.3 16.1 4 20Z"/></svg>
                        </button>
                        <form method="POST" action="{{ route('admin.informasi.hapus') }}" onsubmit="return confirm('Hapus informasi ini? Tindakan ini tidak dapat dibatalkan.');">
                            @csrf
                            <input type="hidden" name="id" value="{{ $item['id'] }}">
                            <button class="info-icon-action is-delete" type="submit" aria-label="Hapus {{ $item['judul'] ?? 'informasi' }}" title="Hapus informasi">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M5.5 7l1 14h11l1-14M9 7V4h6v3"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="info-meta">
                    @if (! empty($item['pengirim']))
                        <span>Dari {{ $item['pengirim'] }}</span>
                    @endif
                    @if (! empty($item['targetKelas']))
                        <span>Untuk {{ $item['targetKelas'] }}</span>
                    @endif
                    <span class="info-publish-badge {{ ($item['published'] ?? false) ? 'is-published' : 'is-draft' }}">{{ ($item['published'] ?? false) ? 'Dipublikasikan' : 'Draf' }}</span>
                    <span>Diperbarui {{ isset($item['updatedAt']) ? \Illuminate\Support\Carbon::parse($item['updatedAt'])->format('d/m/Y H:i') : '-' }}</span>
                </div>
                <p class="info-item-content">{{ $item['isi'] ?? '' }}</p>
                </article>
            @empty
                <div class="card info-empty-state">Belum ada informasi yang dibuat. Buat pengumuman pertama melalui formulir di atas.</div>
            @endforelse
        </div>
    </section>

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
