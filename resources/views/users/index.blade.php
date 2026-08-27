<x-app-layout>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
        <h1 class="font-display text-2xl font-semibold text-ink">Manajemen User</h1>
        @can('create', App\Models\User::class)
            <a href="{{ route('users.create') }}"
               style="background:#0E6E66; color:white; padding:8px 16px; border-radius:6px; font-size:14px; text-decoration:none; font-family:'IBM Plex Sans',sans-serif; font-weight:500">
                + Tambah User
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('users.index') }}">
        <div style="display:flex; gap:8px; align-items:center; margin-bottom:12px">
            <select name="role"
                    style="padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
                <option value="semua" {{ $role === 'semua' ? 'selected' : '' }}>Semua Role</option>
                <option value="bagian_administrasi" {{ $role === 'bagian_administrasi' ? 'selected' : '' }}>Bagian Administrasi</option>
                <option value="bagian_keuangan" {{ $role === 'bagian_keuangan' ? 'selected' : '' }}>Bagian Keuangan</option>
                <option value="pimpinan" {{ $role === 'pimpinan' ? 'selected' : '' }}>Pimpinan</option>
                <option value="sales" {{ $role === 'sales' ? 'selected' : '' }}>Sales / Penagih</option>
            </select>

            <select name="status"
                    style="padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
                <option value="semua" {{ $status === 'semua' ? 'selected' : '' }}>Aktif / Nonaktif</option>
                <option value="aktif" {{ $status === 'aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="nonaktif" {{ $status === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
            </select>

            <button type="submit"
                    style="padding:8px 20px; background:#0E6E66; color:white; border:none; border-radius:6px; font-size:14px; cursor:pointer; font-family:'IBM Plex Sans',sans-serif; font-weight:500">
                Cari
            </button>

            @if($role !== 'semua' || $status !== 'semua')
                <a href="{{ route('users.index') }}"
                   style="padding:8px 16px; border:1px solid #DCE2E0; border-radius:6px; font-size:14px; color:#5B6470; font-family:Inter,sans-serif; text-decoration:none">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <div class="bg-surface border border-line rounded overflow-hidden">
        <div class="hidden sm:block overflow-x-auto">
            <table style="width:100%">
                <thead>
                    <tr class="border-b border-line">
                        <th style="padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Nama</th>
                        <th style="padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Email</th>
                        <th style="padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Role</th>
                        <th style="padding:12px 16px; text-align:center; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Status</th>
                        <th style="padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        @php
                            $colors = [
                                'bagian_administrasi' => ['bg' => '#2563EB', 'label' => 'Bagian Administrasi'],
                                'bagian_keuangan' => ['bg' => '#7C3AED', 'label' => 'Bagian Keuangan'],
                                'pimpinan' => ['bg' => '#0D9488', 'label' => 'Pimpinan'],
                                'sales' => ['bg' => '#EA580C', 'label' => 'Sales / Penagih'],
                            ];
                            $c = $colors[$u->role] ?? ['bg' => '#5B6470', 'label' => $u->role];
                        @endphp
                        <tr class="border-b border-line hover:bg-paper transition">
                            <td style="padding:12px 16px; font-size:14px; font-weight:500; white-space:nowrap">
                                {{ $u->name }}
                                @if($u->id === auth()->id())
                                    <span style="font-size:11px; color:#5B6470; font-weight:400">(Anda)</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; font-size:14px; white-space:nowrap">{{ $u->email }}</td>
                            <td style="padding:12px 16px">
                                <span style="display:inline-block; background:{{ $c['bg'] }}; color:white; padding:2px 10px; border-radius:999px; font-size:12px; font-weight:500; white-space:nowrap">
                                    {{ $c['label'] }}
                                </span>
                            </td>
                            <td style="padding:12px 16px; text-align:center">
                                @if($u->is_active)
                                    <span style="display:inline-block; background:#16A34A; color:white; padding:2px 10px; border-radius:999px; font-size:12px; font-weight:500">&bull; Aktif</span>
                                @else
                                    <span style="display:inline-block; background:#DC2626; color:white; padding:2px 10px; border-radius:999px; font-size:12px; font-weight:500">&bull; Nonaktif</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; text-align:right; white-space:nowrap">
                                @can('update', $u)
                                    <a href="{{ route('users.edit', $u) }}"
                                       style="padding:2px 8px; border:1px solid #0E6E66; color:#0E6E66; background:white; border-radius:4px; font-size:11px; text-decoration:none; font-family:Inter,sans-serif; font-weight:500">
                                        Edit
                                    </a>
                                @endcan
                                @if($u->id !== auth()->id())
                                    @can('delete', $u)
                                        <form action="{{ route('users.destroy', $u) }}" method="POST" class="inline" onsubmit="return confirm('{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }} user ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    style="padding:2px 8px; border:1px solid {{ $u->is_active ? '#B33A2E' : '#16A34A' }}; color:{{ $u->is_active ? '#B33A2E' : '#16A34A' }}; background:white; border-radius:4px; font-size:11px; cursor:pointer; font-family:Inter,sans-serif; font-weight:500">
                                                {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:32px; text-align:center; color:#5B6470; font-size:14px">
                                Belum ada user.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sm:hidden divide-y divide-line">
            @forelse ($users as $u)
                @php
                    $colors = [
                        'bagian_administrasi' => ['bg' => '#2563EB', 'label' => 'Bagian Administrasi'],
                        'bagian_keuangan' => ['bg' => '#7C3AED', 'label' => 'Bagian Keuangan'],
                        'pimpinan' => ['bg' => '#0D9488', 'label' => 'Pimpinan'],
                        'sales' => ['bg' => '#EA580C', 'label' => 'Sales / Penagih'],
                    ];
                    $c = $colors[$u->role] ?? ['bg' => '#5B6470', 'label' => $u->role];
                @endphp
                <div class="p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span style="font-size:14px; font-weight:500">{{ $u->name }}</span>
                        @if($u->is_active)
                            <span style="background:#16A34A; color:white; padding:1px 8px; border-radius:999px; font-size:11px">&bull; Aktif</span>
                        @else
                            <span style="background:#DC2626; color:white; padding:1px 8px; border-radius:999px; font-size:11px">&bull; Nonaktif</span>
                        @endif
                    </div>
                    <div style="font-size:13px; color:#5B6470">{{ $u->email }}</div>
                    <div class="flex items-center justify-between">
                        <span style="background:{{ $c['bg'] }}; color:white; padding:1px 8px; border-radius:999px; font-size:11px">{{ $c['label'] }}</span>
                        <div style="display:flex; gap:6px">
                            @can('update', $u)
                                <a href="{{ route('users.edit', $u) }}"
                                   style="padding:2px 8px; border:1px solid #0E6E66; color:#0E6E66; background:white; border-radius:4px; font-size:11px; text-decoration:none">
                                    Edit
                                </a>
                            @endcan
                            @if($u->id !== auth()->id())
                                @can('delete', $u)
                                    <form action="{{ route('users.destroy', $u) }}" method="POST" onsubmit="return confirm('{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }} user ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                style="padding:2px 8px; border:1px solid {{ $u->is_active ? '#B33A2E' : '#16A34A' }}; color:{{ $u->is_active ? '#B33A2E' : '#16A34A' }}; background:white; border-radius:4px; font-size:11px; cursor:pointer">
                                            {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center" style="color:#5B6470; font-size:14px">Belum ada user.</div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-line hidden sm:block">
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
