<x-app-layout>
    <x-slot name="header">Tagihan</x-slot>

    <div class="bg-surface border border-line rounded overflow-hidden">
        <div class="px-4 py-3 border-b border-line flex items-center justify-between">
            <p class="text-sm text-ink-muted">{{ $tagihan->total() }} tagihan</p>
            @can('create', App\Models\Tagihan::class)
                <a href="{{ route('tagihan.create') }}" class="btn-primary">
                    + Buat Tagihan
                </a>
            @endcan
        </div>

        <div class="overflow-x-auto">
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
                            <td class="table-cell text-right font-mono">Rp {{ number_format($t->total_tagihan, 2) }}</td>
                            <td class="table-cell">
                                <span class="{{ $badge }}">{{ $badgeText }}</span>
                            </td>
                            <td class="table-cell text-right whitespace-nowrap">
                                @can('update', $t)
                                    <a href="{{ route('tagihan.edit', $t) }}" class="text-ink-muted hover:text-ink text-sm transition">Edit</a>
                                @endcan
                                @can('delete', $t)
                                    <form action="{{ route('tagihan.destroy', $t) }}" method="POST" class="inline" onsubmit="return confirm('Hapus tagihan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-2 text-status-critical hover:text-status-critical text-sm transition">Hapus</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-ink-muted text-sm">
                                Belum ada tagihan.
                                @can('create', App\Models\Tagihan::class)
                                    <a href="{{ route('tagihan.create') }}" class="text-action hover:underline">Buat tagihan baru</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-line">
            {{ $tagihan->links() }}
        </div>
    </div>
</x-app-layout>
