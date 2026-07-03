<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    @php
        $role = Auth::user()->role;
    @endphp

    @if ($role === 'bagian_administrasi')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
            <div class="bg-surface border border-line rounded p-4">
                <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Tagihan Belum Lunas</p>
                <p class="text-2xl font-mono font-semibold text-ink mt-1">{{ $tagihanBelumLunas }}</p>
            </div>
            <div class="bg-surface border border-line rounded p-4">
                <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Jatuh Tempo Minggu Ini</p>
                <p class="text-2xl font-mono font-semibold text-status-watch30 mt-1">{{ $tagihanJatuhTempoMingguIni }}</p>
            </div>
            <div class="bg-surface border border-line rounded p-4">
                <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Total Piutang</p>
                <p class="text-2xl font-mono font-semibold text-ink mt-1">Rp {{ number_format($totalPiutang, 0) }}</p>
            </div>
        </div>

        <div class="bg-surface border border-line rounded overflow-hidden">
            <div class="px-4 py-3 border-b border-line">
                <h2 class="font-display text-lg font-semibold text-ink">Tagihan Terbaru</h2>
            </div>

            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="table-header">Invoice</th>
                            <th class="table-header">Pelanggan</th>
                            <th class="table-header text-right">Total</th>
                            <th class="table-header">Status</th>
                            <th class="table-header">Jatuh Tempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tagihanTerbaru as $t)
                            @php
                                if ($t->status === 'lunas') {
                                    $rail = 'paid';
                                    $badge = 'badge-paid';
                                    $statusLabel = 'Lunas';
                                } elseif ($t->is_overdue) {
                                    $bucket = $t->aging_bucket;
                                    $rail = $bucket === '0-30' ? 'watch30' : ($bucket === '31-60' ? 'watch60' : 'critical');
                                    $badge = $bucket === '0-30' ? 'badge-watch30' : ($bucket === '31-60' ? 'badge-watch60' : 'badge-critical');
                                    $statusLabel = $bucket === '0-30' ? '1-30 Hari' : ($bucket === '31-60' ? '31-60 Hari' : '>60 Hari');
                                } else {
                                    $rail = 'lancar';
                                    $badge = 'badge-lancar';
                                    $statusLabel = 'Belum Lunas';
                                }
                            @endphp
                            <tr class="border-b border-line hover:bg-paper transition aging-rail-{{ $rail }}">
                                <td class="table-cell">
                                    <a href="{{ route('tagihan.show', $t) }}" class="text-action hover:underline font-mono font-medium">
                                        {{ $t->no_invoice }}
                                    </a>
                                </td>
                                <td class="table-cell">{{ $t->pelanggan->nama_pelanggan }}</td>
                                <td class="table-cell text-right font-mono">Rp {{ number_format($t->total_tagihan, 0) }}</td>
                                <td class="table-cell"><span class="{{ $badge }}">{{ $statusLabel }}</span></td>
                                <td class="table-cell font-mono">{{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-ink-muted text-sm">
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

            <div class="sm:hidden divide-y divide-line">
                @forelse ($tagihanTerbaru as $t)
                    @php
                        if ($t->status === 'lunas') {
                            $rail = 'aging-rail-paid';
                            $badge = 'badge-paid';
                            $statusLabel = 'Lunas';
                        } elseif ($t->is_overdue) {
                            $bucket = $t->aging_bucket;
                            $rail = $bucket === '0-30' ? 'aging-rail-watch30' : ($bucket === '31-60' ? 'aging-rail-watch60' : 'aging-rail-critical');
                            $badge = $bucket === '0-30' ? 'badge-watch30' : ($bucket === '31-60' ? 'badge-watch60' : 'badge-critical');
                            $statusLabel = $bucket === '0-30' ? '1-30 Hari' : ($bucket === '31-60' ? '31-60 Hari' : '>60 Hari');
                        } else {
                            $rail = 'aging-rail-lancar';
                            $badge = 'badge-lancar';
                            $statusLabel = 'Belum Lunas';
                        }
                    @endphp
                    <div class="p-4 {{ $rail }} space-y-2">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('tagihan.show', $t) }}" class="text-action hover:underline font-mono font-medium text-sm">
                                {{ $t->no_invoice }}
                            </a>
                            <span class="{{ $badge }}">{{ $statusLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span>{{ $t->pelanggan->nama_pelanggan }}</span>
                            <span class="font-mono">Rp {{ number_format($t->total_tagihan, 0) }}</span>
                        </div>
                        <div class="text-xs text-ink-muted">
                            Jatuh tempo: {{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-ink-muted text-sm">
                        Belum ada tagihan.
                        @can('create', App\Models\Tagihan::class)
                            <a href="{{ route('tagihan.create') }}" class="text-action hover:underline">Buat tagihan baru</a>
                        @endcan
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
            <div class="bg-surface border border-line rounded p-4">
                <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Total Piutang</p>
                <p class="text-2xl font-mono font-semibold text-ink mt-1">Rp {{ number_format($totalPiutang, 0) }}</p>
            </div>
            <div class="bg-surface border border-line rounded p-4">
                <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Total Tertagih</p>
                <p class="text-2xl font-mono font-semibold text-status-paid mt-1">Rp {{ number_format($totalTertagih, 0) }}</p>
            </div>
            <div class="bg-surface border border-line rounded p-4">
                <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Piutang Belum Tertagih</p>
                <p class="text-2xl font-mono font-semibold text-status-watch30 mt-1">Rp {{ number_format(max(0, $totalPiutang - $totalTertagih), 0) }}</p>
            </div>
        </div>

        @php
            $bucketMeta = [
                'lancar' => ['label' => 'Lancar', 'class' => 'aging-rail-lancar', 'badge' => 'badge-lancar'],
                '0-30' => ['label' => '0–30 Hari', 'class' => 'aging-rail-watch30', 'badge' => 'badge-watch30'],
                '31-60' => ['label' => '31–60 Hari', 'class' => 'aging-rail-watch60', 'badge' => 'badge-watch60'],
                '>60' => ['label' => '>60 Hari', 'class' => 'aging-rail-critical', 'badge' => 'badge-critical'],
            ];
        @endphp

        <div class="bg-surface border border-line rounded overflow-hidden">
            <div class="px-4 py-3 border-b border-line">
                <h2 class="font-display text-lg font-semibold text-ink">Ringkasan Umur Piutang</h2>
            </div>
            <div class="divide-y divide-line">
                @foreach ($bucketMeta as $key => $meta)
                    @php
                        $item = $summary[$key] ?? ['total' => 0, 'count' => 0];
                    @endphp
                    <div class="flex items-center justify-between px-4 py-3 {{ $meta['class'] }}">
                        <div class="flex items-center gap-3">
                            <span class="{{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                            <span class="text-xs text-ink-muted">{{ $item['count'] }} tagihan</span>
                        </div>
                        <span class="font-mono text-sm font-medium text-ink">Rp {{ number_format($item['total'], 0) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-app-layout>
