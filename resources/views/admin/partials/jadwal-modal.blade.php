{{-- Shared slide-up modal for editing a schedule slot. --}}
<div class="modal-backdrop" id="jadwal-modal" aria-hidden="true">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="jadwal-modal-title">
        <div class="modal-head">
            <div>
                <h3 id="jadwal-modal-title">Tambah Slot Jadwal</h3>
                <p class="modal-subtitle">Atur guru utama, guru pengganti sementara, dan status jam pelajaran.</p>
            </div>
            <button type="button" class="modal-close" data-modal-close aria-label="Tutup">×</button>
        </div>

        <form method="POST" action="{{ $simpanRoute }}" id="form-jadwal">
            @csrf
            <input type="hidden" name="idx" id="jadwal-idx" value="{{ old('idx') }}">
            <div class="modal-body modal-form-body modal-scroll">
                @if ($errors->has('jadwal'))
                    <div class="alert alert-merah" role="alert" style="margin-bottom:14px;">{{ $errors->first('jadwal') }}</div>
                @endif

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="jadwal-hari">Hari</label>
                        <select class="form-select" id="jadwal-hari" name="hari" required>
                            @foreach ($hari as $h)
                                <option value="{{ $h }}" @selected(old('hari') === $h)>{{ $h }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="jadwal-jam">Jam Pelajaran</label>
                        <select class="form-select" id="jadwal-jam" name="jamKe" required>
                            @foreach ($jam as $jp)
                                <option value="{{ $jp['ke'] }}" @selected((string) old('jamKe') === (string) $jp['ke'])>
                                    Jam {{ $jp['ke'] }} · {{ $jp['mulai'] }}–{{ $jp['selesai'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="jadwal-kelas">Kelas</label>
                        <input class="form-input" id="jadwal-kelas" name="kelas" type="text" list="jadwal-kelas-list" maxlength="30" required>
                        <datalist id="jadwal-kelas-list">
                            @foreach ($daftarKelas as $k)
                                <option value="{{ $k }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="jadwal-ruang">Ruang</label>
                        <input class="form-input" id="jadwal-ruang" name="ruang" type="text" maxlength="30" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="jadwal-mapel">Mata Pelajaran</label>
                        <input class="form-input" id="jadwal-mapel" name="mapel" type="text" maxlength="80" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="jadwal-guru">Guru Utama</label>
                        <select class="form-select" id="jadwal-guru" name="idGuru" required>
                            @foreach ($daftarGuru as $g)
                                <option value="{{ $g['id'] ?? '' }}" @selected(old('idGuru') === ($g['id'] ?? ''))>
                                    {{ $g['nama'] ?? '-' }}{{ ($g['status'] ?? '') === 'Nonaktif' ? ' (Nonaktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="jadwal-status">Status Jadwal</label>
                        <select class="form-select" id="jadwal-status" name="status">
                            <option value="Regular">Regular</option>
                            <option value="Digantikan">Digantikan (guru pengganti)</option>
                            <option value="Kosong">Kosong / tidak ada guru</option>
                        </select>
                    </div>
                    <div class="form-group" id="jadwal-pengganti-wrap">
                        <label class="form-label" for="jadwal-pengganti">Guru Pengganti</label>
                        <select class="form-select" id="jadwal-pengganti" name="idGuruPengganti">
                            <option value="">— Pilih guru pengganti —</option>
                            @foreach ($daftarGuru as $g)
                                @if (($g['status'] ?? '') === 'Aktif')
                                    <option value="{{ $g['id'] ?? '' }}">{{ $g['nama'] ?? '-' }} · {{ $g['mapel'] ?? '-' }}</option>
                                @endif
                            @endforeach
                        </select>
                        <p class="modal-help">Guru utama tetap tersimpan. Pengganti hanya berlaku untuk slot ini.</p>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="jadwal-catatan">Catatan (opsional)</label>
                    <input class="form-input" id="jadwal-catatan" name="catatan" type="text" maxlength="255" placeholder="Contoh: guru utama dinas luar / izin">
                </div>
            </div>
        </form>

        <div class="modal-foot modal-foot-split">
            <button type="submit" form="form-jadwal-hapus" class="btn btn-merah is-hidden" id="jadwal-hapus-btn">Hapus Slot</button>
            <div class="modal-foot-group">
                <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                <button type="submit" form="form-jadwal" class="btn btn-primary" id="jadwal-simpan-btn">Simpan Jadwal</button>
            </div>
        </div>

        <form method="POST" action="{{ $hapusRoute }}" id="form-jadwal-hapus" class="is-hidden">
            @csrf
            <input type="hidden" name="idx" id="jadwal-hapus-idx">
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('jadwal-modal');
        const form = document.getElementById('form-jadwal');
        const title = document.getElementById('jadwal-modal-title');
        const idxInput = document.getElementById('jadwal-idx');
        const hari = document.getElementById('jadwal-hari');
        const jam = document.getElementById('jadwal-jam');
        const kelas = document.getElementById('jadwal-kelas');
        const ruang = document.getElementById('jadwal-ruang');
        const mapel = document.getElementById('jadwal-mapel');
        const guru = document.getElementById('jadwal-guru');
        const status = document.getElementById('jadwal-status');
        const pengganti = document.getElementById('jadwal-pengganti');
        const penggantiWrap = document.getElementById('jadwal-pengganti-wrap');
        const catatan = document.getElementById('jadwal-catatan');
        const hapusBtn = document.getElementById('jadwal-hapus-btn');
        const hapusIdx = document.getElementById('jadwal-hapus-idx');
        const hapusForm = document.getElementById('form-jadwal-hapus');

        const defaultData = {
            idx: '',
            hari: @json(old('hari', $hari[0] ?? 'Senin')),
            jamKe: @json((string) old('jamKe', $jam[0]['ke'] ?? 1)),
            kelas: @json(old('kelas', $kelas ?? '')),
            ruang: @json(old('ruang', '')),
            mapel: @json(old('mapel', '')),
            idGuru: @json(old('idGuru', $idGuru ?? '')),
            status: @json(old('status', 'Regular')),
            idGuruPengganti: @json(old('idGuruPengganti', '')),
            catatan: @json(old('catatan', '')),
        };

        function togglePengganti() {
            const aktif = status.value === 'Digantikan';
            penggantiWrap.classList.toggle('is-hidden', !aktif);
            pengganti.disabled = !aktif;
            if (!aktif) pengganti.value = '';
        }

        function openJadwal(data) {
            const merged = { ...defaultData, ...data };
            idxInput.value = merged.idx ?? '';
            hari.value = merged.hari || defaultData.hari;
            jam.value = String(merged.jamKe || defaultData.jamKe);
            kelas.value = merged.kelas || '';
            ruang.value = merged.ruang || '';
            mapel.value = merged.mapel || '';
            guru.value = merged.idGuru || defaultData.idGuru || guru.options[0]?.value || '';
            status.value = merged.status || 'Regular';
            pengganti.value = merged.idGuruPengganti || '';
            catatan.value = merged.catatan || '';
            togglePengganti();

            const isEdit = merged.idx !== '' && merged.idx !== null && merged.idx !== undefined;
            title.textContent = isEdit ? 'Edit Slot Jadwal' : 'Tambah Slot Jadwal';
            hapusBtn.classList.toggle('is-hidden', !isEdit);
            hapusIdx.value = isEdit ? merged.idx : '';
            window.SigapModal.open('jadwal-modal');
            setTimeout(() => kelas.focus(), 120);
        }

        status.addEventListener('change', togglePengganti);

        document.querySelectorAll('[data-jadwal-slot]').forEach((slot) => {
            slot.addEventListener('click', () => {
                try {
                    openJadwal(JSON.parse(slot.dataset.jadwal));
                } catch (error) {
                    openJadwal({});
                }
            });
        });

        document.querySelectorAll('[data-add-jadwal]').forEach((button) => {
            button.addEventListener('click', () => openJadwal({ ...defaultData, ...(JSON.parse(button.dataset.default || '{}')) }));
        });

        hapusForm.addEventListener('submit', (event) => {
            if (!window.confirm('Hapus slot jadwal ini? Tindakan ini tidak dapat dibatalkan.')) {
                event.preventDefault();
            }
        });

        @if ($errors->hasAny(['idx', 'hari', 'jamKe', 'kelas', 'ruang', 'mapel', 'idGuru', 'status', 'idGuruPengganti', 'catatan', 'jadwal']))
            openJadwal({
                idx: @json(old('idx', '')),
                hari: @json(old('hari', $hari[0] ?? 'Senin')),
                jamKe: @json((string) old('jamKe', $jam[0]['ke'] ?? 1)),
                kelas: @json(old('kelas', $kelas ?? '')),
                ruang: @json(old('ruang', '')),
                mapel: @json(old('mapel', '')),
                idGuru: @json(old('idGuru', $idGuru ?? '')),
                status: @json(old('status', 'Regular')),
                idGuruPengganti: @json(old('idGuruPengganti', '')),
                catatan: @json(old('catatan', '')),
            });
        @endif
    })();
</script>
