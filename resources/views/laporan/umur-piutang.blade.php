<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>Laporan Umur Piutang</span>
            <a href="{{ route('laporan.piutang.export') }}" class="btn-secondary text-sm">Export Excel</a>
        </div>
    </x-slot>

    <div class="space-y-8">
        @foreach (['lancar' => 'Lancar (Belum Jatuh Tempo)', '0-30' => '0–30 Hari', '31-60' => '31–60 Hari', '>60' => '>60 Hari'] as $key => $label)
            @php
                $items = $buckets[$key] ?? collect();
                $totalBucket = $summary[$key]['total'] ?? 0;
                $countBucket = $summary[$key]['count'] ?? 0;
                $railClass = $key === 'lancar' ? 'aging-rail-lancar' : ($key === '0-30' ? 'aging-rail-watch30' : ($key === '31-60' ? 'aging-rail-watch60' : 'aging-rail-critical'));
                $badgeClass = $key === 'lancar' ? 'badge-lancar' : ($key === '0-30' ? 'badge-watch30' : ($key === '31-60' ? 'badge-watch60' : 'badge-critical'));
            @endphp

            <div class="bg-surface border border-line {{ $railClass }} rounded overflow-hidden">
                <div class="px-4 py-3 border-b border-line flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="{{ $badgeClass }} text-sm font-medium">{{ $label }}</span>
                        <span class="text-xs text-ink-muted">{{ $countBucket }} tagihan</span>
                    </div>
                    <span class="font-mono text-sm font-medium text-ink">Rp {{ number_format($totalBucket, 2) }}</span>
                </div>

                @if ($items->isEmpty())
                    <div class="px-4 py-6 text-center text-sm text-ink-muted">
                        Tidak ada tagihan di bucket ini.
                    </div>
                @else
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-line">
                                    <th class="table-header">Invoice</th>
                                    <th class="table-header">Pelanggan</th>
                                    <th class="table-header">Jatuh Tempo</th>
                                    <th class="table-header text-right">Hari Lewat</th>
                                    <th class="table-header text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $t)
                                    <tr class="border-b border-line hover:bg-paper transition {{ $railClass }}">
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
                                        <td class="table-cell font-mono">{{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}</td>
                                        <td class="table-cell text-right font-mono">{{ $t->days_overdue }}</td>
                                        <td class="table-cell text-right font-mono">Rp {{ number_format($t->total_tagihan, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="sm:hidden divide-y divide-line">
                        @foreach ($items as $t)
                            <div class="p-4 {{ $railClass }} space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <a href="{{ route('tagihan.show', $t) }}" class="text-action hover:underline font-mono font-medium">
                                        {{ $t->no_invoice }}
                                    </a>
                                    <span class="font-mono text-ink-muted">Rp {{ number_format($t->total_tagihan, 2) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <a href="{{ route('pelanggan.show', $t->pelanggan) }}" class="text-action hover:underline">
                                        {{ $t->pelanggan->nama_pelanggan }}
                                    </a>
                                    <span class="text-ink-muted">{{ $t->days_overdue }} hari lewat</span>
                                </div>
                                <div class="text-xs text-ink-muted">
                                    Jatuh tempo: {{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        <div class="bg-surface border border-line rounded p-4">
            <div class="flex items-center justify-between text-sm">
                <span class="font-medium text-ink">Total Piutang Belum Lunas</span>
                <span class="font-mono font-semibold text-ink text-lg">Rp {{ number_format(array_sum(array_column($summary, 'total')), 2) }}</span>
            </div>
        </div>
    </div>
</x-app-layout>
