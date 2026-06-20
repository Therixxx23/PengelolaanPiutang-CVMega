<x-app-layout>
    <x-slot name="header">{{ $pelanggan->nama_pelanggan }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-surface border border-line rounded p-6 space-y-4">
                <div>
                    <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Wilayah</p>
                    <p class="text-sm text-ink mt-1">{{ $pelanggan->wilayah ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Alamat</p>
                    <p class="text-sm text-ink mt-1">{{ $pelanggan->alamat ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">No. Telepon</p>
                    <p class="text-sm text-ink font-mono mt-1">{{ $pelanggan->no_telepon ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Batas Kredit</p>
                    <p class="text-sm text-ink font-mono mt-1">Rp {{ number_format($pelanggan->batas_kredit, 2) }}</p>
                </div>
                <div class="pt-3 border-t border-line flex gap-2">
                    @can('update', $pelanggan)
                        <a href="{{ route('pelanggan.edit', $pelanggan) }}" class="btn-secondary">Edit</a>
                    @endcan
                    @can('delete', $pelanggan)
                        <form action="{{ route('pelanggan.destroy', $pelanggan) }}" method="POST" onsubmit="return confirm('Hapus pelanggan ini? Semua tagihan terkait juga akan dihapus.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-destructive">Hapus</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <h2 class="font-display text-xl font-semibold text-ink mb-4">Tagihan</h2>

            <div class="bg-surface border border-line rounded overflow-hidden">
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-line">
                                <th class="table-header">No. Invoice</th>
                                <th class="table-header">Tanggal</th>
                                <th class="table-header">Jatuh Tempo</th>
                                <th class="table-header text-right">Total</th>
                                <th class="table-header text-right">Sisa</th>
                                <th class="table-header">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pelanggan->tagihan as $t)
                                @php
                                    $rail = $t->aging_bucket === 'lunas' ? 'paid' : ($t->aging_bucket === 'lancar' ? 'lancar' : ($t->aging_bucket === '0-30' ? 'watch30' : ($t->aging_bucket === '31-60' ? 'watch60' : 'critical')));
                                    $sisa = $t->pembayaran->sum('jumlah_bayar');
                                    $sisaTagihan = $t->total_tagihan - $sisa;
                                    if ($t->status === 'lunas') {
                                        $badgeClass = 'badge-paid';
                                        $badgeText = 'Lunas';
                                    } elseif ($t->is_overdue) {
                                        $bucket = $t->aging_bucket;
                                        $badgeClass = $bucket === '0-30' ? 'badge-watch30' : ($bucket === '31-60' ? 'badge-watch60' : 'badge-critical');
                                        $badgeText = $bucket === '0-30' ? 'Jatuh Tempo (1-30 hr)' : ($bucket === '31-60' ? 'Jatuh Tempo (31-60 hr)' : 'Jatuh Tempo (>60 hr)');
                                    } else {
                                        $badgeClass = 'badge-lancar';
                                        $badgeText = 'Belum Lunas';
                                    }
                                @endphp
                                <tr class="border-b border-line hover:bg-paper transition aging-rail-{{ $rail }}">
                                    <td class="table-cell font-mono">
                                        <a href="{{ route('tagihan.show', $t) }}" class="text-action hover:underline">
                                            {{ $t->no_invoice }}
                                        </a>
                                    </td>
                                    <td class="table-cell font-mono">{{ $t->tanggal_tagihan->format('d/m/Y') }}</td>
                                    <td class="table-cell font-mono">{{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}</td>
                                    <td class="table-cell text-right font-mono">Rp {{ number_format($t->total_tagihan, 2) }}</td>
                                    <td class="table-cell text-right font-mono">Rp {{ number_format(max(0, $sisaTagihan), 2) }}</td>
                                    <td class="table-cell">
                                        <span class="{{ $badgeClass }}">{{ $badgeText }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-ink-muted text-sm">
                                        Belum ada tagihan untuk pelanggan ini.
                                        @can('create', App\Models\Tagihan::class)
                                            <a href="{{ route('tagihan.create') }}" class="text-action hover:underline">Buat tagihan baru</a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="sm:hidden divide-y divide-line">
                    @forelse ($pelanggan->tagihan as $t)
                        @php
                            $rail = $t->aging_bucket === 'lunas' ? 'aging-rail-paid' : ($t->aging_bucket === 'lancar' ? 'aging-rail-lancar' : ($t->aging_bucket === '0-30' ? 'aging-rail-watch30' : ($t->aging_bucket === '31-60' ? 'aging-rail-watch60' : 'aging-rail-critical')));
                            $sisa = $t->pembayaran->sum('jumlah_bayar');
                            $sisaTagihan = $t->total_tagihan - $sisa;
                            if ($t->status === 'lunas') {
                                $badgeClass = 'badge-paid';
                                $badgeText = 'Lunas';
                            } elseif ($t->is_overdue) {
                                $bucket = $t->aging_bucket;
                                $badgeClass = $bucket === '0-30' ? 'badge-watch30' : ($bucket === '31-60' ? 'badge-watch60' : 'badge-critical');
                                $badgeText = $bucket === '0-30' ? 'Jatuh Tempo (1-30 hr)' : ($bucket === '31-60' ? 'Jatuh Tempo (31-60 hr)' : 'Jatuh Tempo (>60 hr)');
                            } else {
                                $badgeClass = 'badge-lancar';
                                $badgeText = 'Belum Lunas';
                            }
                        @endphp
                        <div class="p-4 {{ $rail }} space-y-2">
                            <div class="flex items-center justify-between">
                                <a href="{{ route('tagihan.show', $t) }}" class="text-action hover:underline font-mono font-medium text-sm">
                                    {{ $t->no_invoice }}
                                </a>
                                <span class="{{ $badgeClass }}">{{ $badgeText }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-ink-muted">Total: <span class="font-mono text-ink">Rp {{ number_format($t->total_tagihan, 2) }}</span></span>
                                <span class="font-mono">Sisa: Rp {{ number_format(max(0, $sisaTagihan), 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-ink-muted">
                                <span>Tagihan: {{ $t->tanggal_tagihan->format('d/m/Y') }}</span>
                                <span>Jatuh tempo: {{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-ink-muted text-sm">
                            Belum ada tagihan untuk pelanggan ini.
                            @can('create', App\Models\Tagihan::class)
                                <a href="{{ route('tagihan.create') }}" class="text-action hover:underline">Buat tagihan baru</a>
                            @endcan
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
