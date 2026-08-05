<x-app-layout>
    <x-slot name="header">Buat Tagihan</x-slot>

    @php
        $threshold = config('piutang.approval_threshold');
        $selectedPelanggan = $pelanggan->firstWhere('id_pelanggan', old('id_pelanggan'));
        $batasKreditAwal = $selectedPelanggan ? (float) $selectedPelanggan->batas_kredit : 0;
        $piutangAktifAwal = $selectedPelanggan ? (float) $selectedPelanggan->total_piutang_aktif : 0;
    @endphp

    <div class="max-w-2xl">
        <form action="{{ route('tagihan.store') }}" method="POST" class="bg-surface border border-line rounded p-6 space-y-4" x-data="{
            totalTagihan: {{ old('total_tagihan', 0) }},
            threshold: {{ $threshold }},
            batasKredit: {{ $batasKreditAwal }},
            piutangAktif: {{ $piutangAktifAwal }},
            init() {
                this.cekThreshold();
            },
            cekThreshold() {
                const total = parseFloat(this.totalTagihan) || 0;
                const butuhApproval =
                    total >= this.threshold ||
                    (this.batasKredit > 0 && (total + this.piutangAktif) > this.batasKredit);
                document.getElementById('approval-warning').style.display = butuhApproval ? 'block' : 'none';
            },
            async fetchPelangganInfo(id) {
                if (!id) return;
                const res = await fetch('/pelanggan/' + id + '/info');
                const data = await res.json();
                this.batasKredit = parseFloat(data.batas_kredit) || 0;
                this.piutangAktif = parseFloat(data.piutang_aktif) || 0;
                this.cekThreshold();
            }
        }">
            @csrf

            <div>
                <label for="id_pelanggan" class="block text-sm font-medium text-ink mb-1">Pelanggan</label>
                <select id="id_pelanggan" name="id_pelanggan" class="input-field" required
                    x-init="
                        $nextTick(() => {
                            const sel = $el;
                            if (sel.value) {
                                sel.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        })
                    "
                    x-on:change="
                        const opt = $event.target.selectedOptions[0];
                        const info = document.getElementById('credit-info');
                        if (opt && opt.dataset.limit !== undefined) {
                            const limit = parseFloat(opt.dataset.limit);
                            const aktif = parseFloat(opt.dataset.aktif);
                            const sisa = Math.max(0, limit - aktif);
                            if (limit > 0) {
                                info.innerHTML = 'Piutang aktif: Rp ' + aktif.toLocaleString('id-ID') + ' &middot; Batas kredit: Rp ' + limit.toLocaleString('id-ID') + ' &middot; Sisa limit: Rp ' + sisa.toLocaleString('id-ID');
                                info.classList.remove('hidden');
                            } else {
                                info.innerHTML = 'Piutang aktif: Rp ' + aktif.toLocaleString('id-ID') + ' &middot; Tanpa batas kredit';
                                info.classList.remove('hidden');
                            }
                        } else {
                            info.classList.add('hidden');
                        }
                        fetchPelangganInfo($event.target.value);
                    "
                >
                    <option value="">Pilih pelanggan...</option>
                    @foreach ($pelanggan as $p)
                        <option value="{{ $p->id_pelanggan }}"
                            data-limit="{{ $p->batas_kredit }}"
                            data-aktif="{{ $p->total_piutang_aktif }}"
                            {{ old('id_pelanggan') == $p->id_pelanggan ? 'selected' : '' }}>
                            {{ $p->nama_pelanggan }} ({{ $p->wilayah ?: '-' }})
                        </option>
                    @endforeach
                </select>
                <p id="credit-info" class="mt-1 text-xs text-ink-muted hidden"></p>
                @error('id_pelanggan') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="no_invoice" class="block text-sm font-medium text-ink mb-1">No. Invoice</label>
                <input type="text" id="no_invoice" value="{{ $noInvoice }}" class="input-field font-mono bg-paper text-ink-muted" readonly>
                <input type="hidden" name="no_invoice" value="{{ $noInvoice }}">
                @error('no_invoice') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="tanggal_tagihan" class="block text-sm font-medium text-ink mb-1">Tanggal Tagihan</label>
                    <input type="date" id="tanggal_tagihan" name="tanggal_tagihan" value="{{ old('tanggal_tagihan', now()->format('Y-m-d')) }}" class="input-field" required>
                    @error('tanggal_tagihan') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tanggal_jatuh_tempo" class="block text-sm font-medium text-ink mb-1">Jatuh Tempo</label>
                    <input type="date" id="tanggal_jatuh_tempo" name="tanggal_jatuh_tempo" value="{{ old('tanggal_jatuh_tempo', now()->addDays(30)->format('Y-m-d')) }}" class="input-field" required>
                    @error('tanggal_jatuh_tempo') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="total_tagihan" class="block text-sm font-medium text-ink mb-1">Total Tagihan (Rp)</label>
                <input type="text" id="total_tagihan" name="total_tagihan" value="{{ old('total_tagihan') }}" class="input-field font-mono text-right" inputmode="numeric" placeholder="0" required
                    x-model="totalTagihan"
                    x-on:input="cekThreshold()">
                @error('total_tagihan') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div id="approval-warning"
                style="display:none; margin-bottom:16px; padding:12px 16px;
                       background:#FFF8F0; border:1px solid #C8862A;
                       border-radius:6px; border-left:3px solid #C8862A">
                <p style="font-size:13px; color:#C8862A; margin:0; font-weight:500">
                    ⚠ Tagihan ini akan memerlukan persetujuan Pimpinan
                </p>
                <p style="font-size:12px; color:#5B6470; margin:4px 0 0">
                    Nilai tagihan melebihi threshold Rp
                    {{ number_format($threshold, 0, ',', '.') }}
                    atau melebihi batas kredit pelanggan. Tagihan akan
                    masuk status "Menunggu Persetujuan" dan belum bisa
                    menerima pembayaran sampai disetujui Pimpinan.
                </p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Tagihan</button>
                <a href="{{ route('tagihan.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>

    @once
        @push('scripts')
        <script>
            document.getElementById('tanggal_tagihan')?.addEventListener('change', function() {
                const jatuhTempo = document.getElementById('tanggal_jatuh_tempo');
                const date = new Date(this.value);
                date.setDate(date.getDate() + 30);
                jatuhTempo.value = date.toISOString().split('T')[0];
            });
        </script>
        @endpush
    @endonce
</x-app-layout>
