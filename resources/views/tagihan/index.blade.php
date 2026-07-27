<x-app-layout>
    <x-slot name="header">Tagihan</x-slot>

    <div class="bg-surface border border-line rounded overflow-hidden">
        <div class="px-4 py-3 border-b border-line">
            <form method="GET" action="{{ route('tagihan.index') }}" class="flex flex-col sm:flex-row gap-2 sm:items-center">
                <input type="text" name="search"
                       value="{{ $search }}"
                       placeholder="Cari no. invoice atau nama pelanggan..."
                       class="flex-1 px-3 py-2 border border-line rounded text-sm font-sans text-ink outline-focus">
                <select name="status"
                        class="px-3 py-2 border border-line rounded text-sm font-sans text-ink outline-focus sm:min-w-[150px]">
                    <option value="semua" {{ $status === 'semua' ? 'selected' : '' }}>Semua Status</option>
                    <option value="belum_lunas" {{ $status === 'belum_lunas' ? 'selected' : '' }}>Belum Lunas</option>
                    <option value="lunas" {{ $status === 'lunas' ? 'selected' : '' }}>Lunas</option>
                </select>
                <button type="submit" class="btn-primary !py-2">Cari</button>
                @if($search || $status !== 'semua')
                    <a href="{{ route('tagihan.index') }}" class="px-4 py-2 border border-line rounded text-sm text-ink-muted font-sans hover:bg-paper transition text-center">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="px-4 py-3 border-b border-line flex items-center justify-between">
            <p class="text-sm text-ink-muted">
                @if($search || $status !== 'semua')
                    Menampilkan {{ $tagihan->total() }} dari {{ $totalSemua }} tagihan
                    @if($search) untuk "{{ $search }}"@endif
                    @if($status !== 'semua') · {{ $status === 'belum_lunas' ? 'Belum Lunas' : 'Lunas' }}@endif
                @else
                    {{ $totalSemua }} tagihan
                @endif
            </p>
            @can('create', App\Models\Tagihan::class)
                <a href="{{ route('tagihan.create') }}" class="btn-primary">
                    + Buat Tagihan
                </a>
            @endcan
        </div>

        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-line">
                        <th class="table-header">No. Invoice</th>
                        <th class="table-header">Pelanggan</th>
                        <th class="table-header">Tanggal</th>
                        <th class="table-header">Jatuh Tempo</th>
                        <th class="table-header text-right">Total</th>
                        <th class="table-header">Status</th>
                        <th class="table-header text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tagihan as $t)
                        @php
                            if ($t->status === 'lunas') {
                                $rail = 'paid';
                                $badge = 'badge-paid';
                                $badgeText = 'Lunas';
                            } elseif ($t->is_overdue) {
                                $bucket = $t->aging_bucket;
                                $rail = $bucket === '0-30' ? 'watch30' : ($bucket === '31-60' ? 'watch60' : 'critical');
                                $badge = $bucket === '0-30' ? 'badge-watch30' : ($bucket === '31-60' ? 'badge-watch60' : 'badge-critical');
                                $badgeText = $bucket === '0-30' ? '1-30 Hari' : ($bucket === '31-60' ? '31-60 Hari' : '>60 Hari');
                            } else {
                                $rail = 'lancar';
                                $badge = 'badge-lancar';
                                $badgeText = 'Belum Lunas';
                            }
                        @endphp
                        <tr class="border-b border-line hover:bg-paper transition aging-rail-{{ $rail }}">
                            <td class="table-cell">
                                <a href="{{ route('tagihan.show', $t) }}" class="text-action hover:underline font-mono font-medium">
                                    {{ $t->no_invoice }}
                                </a>
                            </td>
                            <td class="table-cell">
                                <a href="{{ route('pelanggan.show', $t->pelanggan) }}" class="text-action hover:underline">
                                    {{ $t->pelanggan->nama_pelanggan }}
                                </a>
                            </td>
                            <td class="table-cell font-mono text-sm">{{ $t->tanggal_tagihan->format('d/m/Y') }}</td>
                            <td class="table-cell font-mono text-sm">{{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}</td>
                            <td class="table-cell rupiah">Rp {{ number_format($t->total_tagihan, 2, ',', '.') }}</td>
                            <td class="table-cell">
                                <span class="{{ $badge }}">{{ $badgeText }}</span>
                            </td>
                            <td class="table-cell text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    @can('update', $t)
                                        <a href="{{ route('tagihan.edit', $t) }}" class="btn-edit !py-1 !px-2 text-xs">Edit</a>
                                    @endcan
                                    @can('delete', $t)
                                        <form action="{{ route('tagihan.destroy', $t) }}" method="POST" onsubmit="return confirm('Hapus tagihan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-destructive !py-1 !px-2 text-xs">Hapus</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-ink-muted text-sm">
                                @if($search || $status !== 'semua')
                                    Tidak ada tagihan yang cocok dengan pencarian ini.
                                    <a href="{{ route('tagihan.index') }}" class="text-action hover:underline">Reset pencarian</a>
                                @else
                                    Belum ada tagihan.
                                    @can('create', App\Models\Tagihan::class)
                                        <a href="{{ route('tagihan.create') }}" class="text-action hover:underline">Buat tagihan baru</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sm:hidden divide-y divide-line">
            @forelse ($tagihan as $t)
                @php
                    if ($t->status === 'lunas') {
                        $rail = 'aging-rail-paid';
                        $badge = 'badge-paid';
                        $badgeText = 'Lunas';
                    } elseif ($t->is_overdue) {
                        $bucket = $t->aging_bucket;
                        $rail = $bucket === '0-30' ? 'aging-rail-watch30' : ($bucket === '31-60' ? 'aging-rail-watch60' : 'aging-rail-critical');
                        $badge = $bucket === '0-30' ? 'badge-watch30' : ($bucket === '31-60' ? 'badge-watch60' : 'badge-critical');
                        $badgeText = $bucket === '0-30' ? '1-30 Hari' : ($bucket === '31-60' ? '31-60 Hari' : '>60 Hari');
                    } else {
                        $rail = 'aging-rail-lancar';
                        $badge = 'badge-lancar';
                        $badgeText = 'Belum Lunas';
                    }
                @endphp
                <div class="p-4 {{ $rail }} space-y-2">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('tagihan.show', $t) }}" class="text-action hover:underline font-mono font-medium text-sm">
                            {{ $t->no_invoice }}
                        </a>
                        <span class="{{ $badge }}">{{ $badgeText }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <a href="{{ route('pelanggan.show', $t->pelanggan) }}" class="text-action hover:underline">
                            {{ $t->pelanggan->nama_pelanggan }}
                        </a>
                        <span class="rupiah text-ink-muted">Rp {{ number_format($t->total_tagihan, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-ink-muted">
                        <span>Tagihan: {{ $t->tanggal_tagihan->format('d/m/Y') }}</span>
                        <span>Jatuh tempo: {{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex gap-2 pt-1">
                        @can('update', $t)
                            <a href="{{ route('tagihan.edit', $t) }}" class="btn-edit !py-1 !px-2 text-xs">Edit</a>
                        @endcan
                        @can('delete', $t)
                            <form action="{{ route('tagihan.destroy', $t) }}" method="POST" onsubmit="return confirm('Hapus tagihan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-destructive !py-1 !px-2 text-xs">Hapus</button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-ink-muted text-sm">
                    @if($search || $status !== 'semua')
                        Tidak ada tagihan yang cocok dengan pencarian ini.
                        <a href="{{ route('tagihan.index') }}" class="text-action hover:underline">Reset pencarian</a>
                    @else
                        Belum ada tagihan.
                        @can('create', App\Models\Tagihan::class)
                            <a href="{{ route('tagihan.create') }}" class="text-action hover:underline">Buat tagihan baru</a>
                        @endcan
                    @endif
                </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-line hidden sm:block">
            {{ $tagihan->links() }}
        </div>
    </div>
</x-app-layout>
