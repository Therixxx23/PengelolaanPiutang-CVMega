<x-app-layout>
    <x-slot name="header">Edit User</x-slot>

    <div class="max-w-2xl">
        <form action="{{ route('users.update', $user) }}" method="POST" class="bg-surface border border-line rounded p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-ink mb-1">Nama Lengkap</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="input-field" required>
                @error('name') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-ink mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="input-field" required>
                @error('email') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-ink mb-1">Role</label>
                <select id="role" name="role" class="input-field" required>
                    <option value="bagian_administrasi" {{ old('role', $user->role) === 'bagian_administrasi' ? 'selected' : '' }}>Bagian Administrasi</option>
                    <option value="bagian_keuangan" {{ old('role', $user->role) === 'bagian_keuangan' ? 'selected' : '' }}>Bagian Keuangan</option>
                    <option value="pimpinan" {{ old('role', $user->role) === 'pimpinan' ? 'selected' : '' }}>Pimpinan</option>
                    <option value="sales" {{ old('role', $user->role) === 'sales' ? 'selected' : '' }}>Sales / Penagih</option>
                </select>
                @error('role') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-ink mb-1">Password</label>
                <input type="password" id="password" name="password" class="input-field">
                <p class="mt-1 text-xs text-ink-muted">Kosongkan jika tidak ingin mengganti password. Min. 8 karakter, harus ada huruf besar dan angka</p>
                @error('password') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-ink mb-1">Konfirmasi Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="input-field">
                @error('password') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                       @if((bool) old('is_active', $user->is_active)) checked @endif
                       class="accent-[#0E6E66] h-4 w-4">
                <label for="is_active" class="text-sm font-medium text-ink">Status Aktif</label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
                <a href="{{ route('users.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
