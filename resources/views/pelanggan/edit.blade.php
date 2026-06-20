<x-app-layout>
    <x-slot name="header">Edit Pelanggan</x-slot>

    <div class="max-w-2xl">
        <form action="{{ route('pelanggan.update', $pelanggan) }}" method="POST" class="bg-surface border border-line rounded p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="nama_pelanggan" class="block text-sm font-medium text-ink mb-1">Nama Pelanggan</label>
                <input type="text" id="nama_pelanggan" name="nama_pelanggan" value="{{ old('nama_pelanggan', $pelanggan->nama_pelanggan) }}" class="input-field" required>
                @error('nama_pelanggan') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="wilayah" class="block text-sm font-medium text-ink mb-1">Wilayah</label>
                <input type="text" id="wilayah" name="wilayah" value="{{ old('wilayah', $pelanggan->wilayah) }}" class="input-field">
                @error('wilayah') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="alamat" class="block text-sm font-medium text-ink mb-1">Alamat</label>
                <textarea id="alamat" name="alamat" rows="3" class="input-field">{{ old('alamat', $pelanggan->alamat) }}</textarea>
                @error('alamat') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="no_telepon" class="block text-sm font-medium text-ink mb-1">No. Telepon</label>
                <input type="text" id="no_telepon" name="no_telepon" value="{{ old('no_telepon', $pelanggan->no_telepon) }}" class="input-field">
                @error('no_telepon') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="batas_kredit" class="block text-sm font-medium text-ink mb-1">Batas Kredit (Rp)</label>
                <input type="text" id="batas_kredit" name="batas_kredit" value="{{ old('batas_kredit', $pelanggan->batas_kredit) }}" class="input-field font-mono text-right" inputmode="numeric">
                @error('batas_kredit') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
                <a href="{{ route('pelanggan.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
