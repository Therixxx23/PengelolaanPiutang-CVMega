<x-app-layout>
    <x-slot name="header">Laporan Rekapitulasi</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
        <div class="bg-surface border border-line rounded p-4">
            <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Total Piutang</p>
            <p class="text-xl font-mono font-semibold text-ink mt-1">Rp {{ number_format($totalPiutang, 2) }}</p>
        </div>
        <div class="bg-surface border border-line rounded p-4">
            <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Total Tertagih</p>
            <p class="text-xl font-mono font-semibold text-status-paid mt-1">Rp {{ number_format($totalTertagih, 2) }}</p>
        </div>
        <div class="bg-surface border border-line rounded p-4">
            <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Total Pelanggan</p>
            <p class="text-xl font-mono font-semibold text-ink mt-1">{{ $ringkasan->count() }}</p>
        </div>
    </div>

    <div class="bg-surface border border-line rounded overflow-hidden mb-8">
        <div class="px-4 py-3 border-b border-line">
            <h2 class="font-display text-lg font-semibold text-ink">Sisa Piutang per Pelanggan</h2>
        </div>
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-line">
                        <th class="table-header">Pelanggan</th>
                        <th class="table-header">Wilayah</th>
                        <th class="table-header text-right">Total Tagihan</th>
                        <th class="table-header text-right">Total Terbayar</th>
                        <th class="table-header text-right">Sisa Piutang</th>
                        <th class="table-header">Status Terburuk</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ringkasan as $r)
                        @php
                            $badgeMap = [
                                'lancar' => 'badge-lancar',
                                '0-30' => 'badge-watch30',
                                '31-60' => 'badge-watch60',
                                '>60' => 'badge-critical',
                            ];
                            $labelMap = [
                                'lancar' => 'Lancar',
                                '0-30' => '0-30 Hari',
                                '31-60' => '31-60 Hari',
                                '>60' => '>60 Hari',
                            ];
                            $worst = $r->bucket_terburuk;
                        @endphp
                        <tr class="border-b border-line hover:bg-paper transition">
                            <td class="table-cell">
                                <a href="{{ route('pelanggan.show', $r->pelanggan) }}" class="text-action hover:underline font-medium">
                                    {{ $r->pelanggan->nama_pelanggan }}
                                </a>
                            </td>
                            <td class="table-cell">{{ $r->pelanggan->wilayah ?: '-' }}</td>
                            <td class="table-cell text-right font-mono">Rp {{ number_format($r->total_tagihan, 2) }}</td>
                            <td class="table-cell text-right font-mono">Rp {{ number_format($r->total_terbayar, 2) }}</td>
                            <td class="table-cell text-right font-mono font-medium {{ $r->sisa_piutang > 0 ? 'text-status-watch30' : 'text-status-paid' }}">
                                Rp {{ number_format($r->sisa_piutang, 2) }}
                            </td>
                            <td class="table-cell">
                                @if ($worst)
                                    <span class="{{ $badgeMap[$worst] ?? 'badge-lancar' }}">{{ $labelMap[$worst] ?? $worst }}</span>
                                @else
                                    <span class="badge-paid">Lunas</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-ink-muted text-sm">
                                Belum ada data piutang untuk ditampilkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sm:hidden divide-y divide-line">
            @forelse ($ringkasan as $r)
                @php
                    $badgeMap = [
                        'lancar' => 'badge-lancar',
                        '0-30' => 'badge-watch30',
                        '31-60' => 'badge-watch60',
                        '>60' => 'badge-critical',
                    ];
                    $labelMap = [
                        'lancar' => 'Lancar',
                        '0-30' => '0-30 Hari',
                        '31-60' => '31-60 Hari',
                        '>60' => '>60 Hari',
                    ];
                    $worst = $r->bucket_terburuk;
                @endphp
                <div class="p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('pelanggan.show', $r->pelanggan) }}" class="text-action hover:underline font-medium text-sm">
                            {{ $r->pelanggan->nama_pelanggan }}
                        </a>
                        @if ($worst)
                            <span class="{{ $badgeMap[$worst] ?? 'badge-lancar' }}">{{ $labelMap[$worst] ?? $worst }}</span>
                        @else
                            <span class="badge-paid">Lunas</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-ink-muted">{{ $r->pelanggan->wilayah ?: '-' }}</span>
                        <span class="font-mono {{ $r->sisa_piutang > 0 ? 'text-status-watch30' : 'text-status-paid' }}">
                            Sisa: Rp {{ number_format($r->sisa_piutang, 2) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-ink-muted">
                        <span>Tagihan: Rp {{ number_format($r->total_tagihan, 2) }}</span>
                        <span>Terbayar: Rp {{ number_format($r->total_terbayar, 2) }}</span>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-ink-muted text-sm">
                    Belum ada data piutang untuk ditampilkan.
                </div>
            @endforelse
        </div>
    </div>

    @if (count($chartLabels) > 0)
        <div class="bg-surface border border-line rounded p-6">
            <h2 class="font-display text-lg font-semibold text-ink mb-4">Sisa Piutang per Pelanggan</h2>
            <div class="relative" style="height: 300px;">
                <canvas id="rekapChart"></canvas>
            </div>
        </div>

        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            new Chart(document.getElementById('rekapChart'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartLabels) !!},
                    datasets: [{
                        label: 'Sisa Piutang (Rp)',
                        data: {!! json_encode($chartData) !!},
                        backgroundColor: 'rgba(14, 110, 102, 0.7)',
                        borderColor: '#0E6E66',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(v) { return 'Rp ' + v.toLocaleString('id-ID'); }
                            }
                        }
                    }
                }
            });
        </script>
        @endpush
    @endif
</x-app-layout>
