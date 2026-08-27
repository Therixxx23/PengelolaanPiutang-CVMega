<x-app-layout>
    <x-slot name="header">Tambah User</x-slot>

    <div class="max-w-2xl">
        <form action="{{ route('users.store') }}" method="POST" class="bg-surface border border-line rounded p-6 space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-ink mb-1">Nama Lengkap</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="input-field" required>
                @error('name') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-ink mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" class="input-field" required>
                @error('email') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-ink mb-1">Role</label>
                <select id="role" name="role" class="input-field" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="bagian_administrasi" {{ old('role') === 'bagian_administrasi' ? 'selected' : '' }}>Bagian Administrasi</option>
                    <option value="bagian_keuangan" {{ old('role') === 'bagian_keuangan' ? 'selected' : '' }}>Bagian Keuangan</option>
                    <option value="pimpinan" {{ old('role') === 'pimpinan' ? 'selected' : '' }}>Pimpinan</option>
                    <option value="sales" {{ old('role') === 'sales' ? 'selected' : '' }}>Sales / Penagih</option>
                </select>
                @error('role') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-ink mb-1">Password</label>
                <input type="password" id="password" name="password" class="input-field" required>
                @error('password') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-ink mb-1">Konfirmasi Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="input-field" required>
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" checked class="accent-[#0E6E66] h-4 w-4">
                <label for="is_active" class="text-sm font-medium text-ink">Status Aktif</label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan User</button>
                <a href="{{ route('users.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
