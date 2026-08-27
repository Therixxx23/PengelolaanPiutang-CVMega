<x-app-layout>
    <x-slot name="header">Import SIPLAH</x-slot>

    <div class="max-w-2xl">
        <div class="bg-surface border border-line rounded p-6 space-y-4">
            <div>
                <h2 class="font-display text-lg font-semibold text-ink">Unggah Data Tagihan dari SIPLAH</h2>
                <p class="text-sm text-ink-muted mt-1">
                    Unggah file <span class="font-mono">.xlsx</span> atau <span class="font-mono">.csv</span> hasil
                    ekspor SIPLAH. Faktur yang nomornya sudah terdaftar akan dilewati (tidak diduplikasi),
                    pelanggan baru akan dibuat otomatis.
                </p>
            </div>

            <form action="{{ route('import.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label for="file" class="block text-sm font-medium text-ink mb-1">File Excel</label>
                    <input type="file" id="file" name="file" accept=".xlsx,.csv"
                        class="input-field" required>
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="px-4 py-2 rounded text-sm font-semibold bg-ink text-white hover:bg-ink-muted transition">
                        Pratinjau File
                    </button>
                    <a href="{{ route('tagihan.index') }}"
                        class="text-sm text-ink-muted hover:text-ink transition">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
