<x-app-layout>
    <x-slot name="header">Buat Tagihan</x-slot>

    <div class="max-w-2xl">
        <form action="{{ route('tagihan.store') }}" method="POST" class="bg-surface border border-line rounded p-6 space-y-4">
            @csrf

            <div>
                <label for="id_pelanggan" class="block text-sm font-medium text-ink mb-1">Pelanggan</label>
                <select id="id_pelanggan" name="id_pelanggan" class="input-field" required>
                    <option value="">Pilih pelanggan...</option>
                    @foreach ($pelanggan as $p)
                        <option value="{{ $p->id_pelanggan }}" {{ old('id_pelanggan') == $p->id_pelanggan ? 'selected' : '' }}>
                            {{ $p->nama_pelanggan }} ({{ $p->wilayah ?: '-' }})
                        </option>
                    @endforeach
                </select>
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
                <input type="text" id="total_tagihan" name="total_tagihan" value="{{ old('total_tagihan') }}" class="input-field font-mono text-right" inputmode="numeric" placeholder="0" required>
                @error('total_tagihan') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
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
