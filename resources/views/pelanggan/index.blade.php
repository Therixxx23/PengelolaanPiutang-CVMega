<x-app-layout>
    <x-slot name="header">Pelanggan</x-slot>

    <div class="bg-surface border border-line rounded overflow-hidden">
        <div class="px-4 py-3 border-b border-line flex items-center justify-between">
            <p class="text-sm text-ink-muted">{{ $pelanggan->total() }} pelanggan</p>
            @can('create', App\Models\Pelanggan::class)
                <a href="{{ route('pelanggan.create') }}" class="btn-primary">
                    + Tambah Pelanggan
                </a>
            @endcan
        </div>

        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-line">
                        <th class="table-header">Nama</th>
                        <th class="table-header">Wilayah</th>
                        <th class="table-header">Telepon</th>
                        <th class="table-header text-right">Batas Kredit</th>
                        <th class="table-header text-right">Tagihan Aktif</th>
                        <th class="table-header text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pelanggan as $p)
                        <tr class="border-b border-line hover:bg-paper transition">
                            <td class="table-cell">
                                <a href="{{ route('pelanggan.show', $p) }}" class="text-action hover:underline font-medium">
                                    {{ $p->nama_pelanggan }}
                                </a>
                            </td>
                            <td class="table-cell">{{ $p->wilayah ?: '-' }}</td>
                            <td class="table-cell font-mono">{{ $p->no_telepon ?: '-' }}</td>
                            <td class="table-cell text-right font-mono">Rp {{ number_format($p->batas_kredit, 2) }}</td>
                            <td class="table-cell text-right font-mono">
                                {{ $p->tagihan()->where('status', 'belum_lunas')->count() }}
                            </td>
                            <td class="table-cell text-right whitespace-nowrap">
                                @can('update', $p)
                                    <a href="{{ route('pelanggan.edit', $p) }}" class="text-ink-muted hover:text-ink text-sm transition">Edit</a>
                                @endcan
                                @can('delete', $p)
                                    <form action="{{ route('pelanggan.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pelanggan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-destructive !py-1 !px-2 text-xs">Hapus</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-ink-muted text-sm">
                                Belum ada pelanggan.
                                @can('create', App\Models\Pelanggan::class)
                                    <a href="{{ route('pelanggan.create') }}" class="text-action hover:underline">Tambah pelanggan baru</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sm:hidden divide-y divide-line">
            @forelse ($pelanggan as $p)
                <div class="p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('pelanggan.show', $p) }}" class="text-action hover:underline font-medium text-sm">
                            {{ $p->nama_pelanggan }}
                        </a>
                        <span class="text-xs text-ink-muted">{{ $p->wilayah ?: '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-ink-muted">{{ $p->no_telepon ?: '-' }}</span>
                        <span class="font-mono">Tagihan aktif: {{ $p->tagihan()->where('status', 'belum_lunas')->count() }}</span>
                    </div>
                    <div class="text-sm">
                        <span class="text-ink-muted">Batas kredit:</span>
                        <span class="font-mono ml-1">Rp {{ number_format($p->batas_kredit, 2) }}</span>
                    </div>
                    <div class="flex gap-2 pt-1">
                        @can('update', $p)
                            <a href="{{ route('pelanggan.edit', $p) }}" class="text-xs text-ink-muted hover:text-ink transition">Edit</a>
                        @endcan
                        @can('delete', $p)
                            <form action="{{ route('pelanggan.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pelanggan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-destructive !py-1 !px-2 text-xs">Hapus</button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-ink-muted text-sm">
                    Belum ada pelanggan.
                    @can('create', App\Models\Pelanggan::class)
                        <a href="{{ route('pelanggan.create') }}" class="text-action hover:underline">Tambah pelanggan baru</a>
                    @endcan
                </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-line hidden sm:block">
            {{ $pelanggan->links() }}
        </div>
    </div>
</x-app-layout>
