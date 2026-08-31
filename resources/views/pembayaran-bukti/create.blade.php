<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>Unggah Bukti Pembayaran</span>
            <a href="{{ route('pembayaran-bukti.index') }}" class="btn-secondary">Kembali</a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <form action="{{ route('pembayaran-bukti.store') }}" method="POST" enctype="multipart/form-data"
              class="bg-surface border border-line rounded p-6 space-y-4">
            @csrf

            <div>
                <label for="tagihan_id" class="block text-sm font-medium text-ink mb-1">Pilih Tagihan</label>
                <select id="tagihan_id" name="tagihan_id" class="input-field" required>
                    <option value="">-- Pilih Tagihan --</option>
                    @foreach ($tagihanBelumLunas as $t)
                        <option value="{{ $t->id_tagihan }}" {{ old('tagihan_id') == $t->id_tagihan ? 'selected' : '' }}>
                            {{ $t->no_invoice }} — {{ $t->pelanggan->nama_pelanggan }} (Sisa: Rp {{ number_format($t->total_tagihan - $t->pembayaran->sum('jumlah_bayar'), 2, ',', '.') }})
                        </option>
                    @endforeach
                </select>
                @error('tagihan_id') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="file" class="block text-sm font-medium text-ink mb-1">File Bukti (JPG / PNG / PDF, maks 5 MB)</label>
                <input type="file" id="file" name="file" accept=".jpg,.jpeg,.png,.pdf" class="input-field" required>
                @error('file') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="nominal_dibayar" class="block text-sm font-medium text-ink mb-1">Nominal Dibayar (Rp)</label>
                <input type="number" id="nominal_dibayar" name="nominal_dibayar" value="{{ old('nominal_dibayar') }}"
                       class="input-field font-mono text-right" inputmode="numeric" min="1000" step="0.01" placeholder="0" required>
                @error('nominal_dibayar') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="tanggal_bayar" class="block text-sm font-medium text-ink mb-1">Tanggal Bayar</label>
                <input type="date" id="tanggal_bayar" name="tanggal_bayar" value="{{ old('tanggal_bayar', now()->format('Y-m-d')) }}"
                       class="input-field" required max="{{ now()->format('Y-m-d') }}">
                @error('tanggal_bayar') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Unggah Bukti</button>
                <a href="{{ route('pembayaran-bukti.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
