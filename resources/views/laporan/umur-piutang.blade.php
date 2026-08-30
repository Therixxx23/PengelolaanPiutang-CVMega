<x-app-layout>
    <div class="space-y-4">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl font-semibold text-ink">Laporan Umur Piutang</h1>
                <p class="text-sm text-ink-muted mt-0.5">CV. Mega Setia Abadi</p>
            </div>
            <a href="{{ route('laporan.piutang.export', array_merge(
                $bucket !== 'semua' ? ['bucket' => $bucket] : [],
                $periode !== 'semua' ? ['periode' => $periode] : []
            )) }}" class="btn-secondary text-sm whitespace-nowrap self-start">Export Excel</a>
        </div>
        {{-- Filter Umur Piutang --}}
        <div>
            <p class="text-xs text-ink-muted font-medium mb-2">Filter Umur Piutang:</p>
            <div class="flex flex-wrap gap-2">
                @php $totalCount = array_sum(array_column($summary, 'count')); @endphp
                @foreach (['semua' => 'Semua', 'lancar' => 'Lancar', '0-30' => '0–30 Hari', '31-60' => '31–60 Hari', '>60' => '>60 Hari'] as $key => $label)
                    @php
                        $count = $key === 'semua' ? $totalCount : ($summary[$key]['count'] ?? 0);
                        $isActive = $bucket === $key;
                        $colorMap = [
                            'semua' => '#1B2027',
                            'lancar' => '#6B7CA3',
                            '0-30' => '#C8862A',
                            '31-60' => '#B8612A',
                            '>60' => '#B33A2E',
                        ];
                        $color = $colorMap[$key] ?? '#1B2027';
                        $periodeParam = $periode !== 'semua' ? ['periode' => $periode] : [];
                        if ($isActive) {
                            $btnStyle = "background-color:{$color}; color:#ffffff; border:2px solid {$color}";
                            $url = route('laporan.umur-piutang', $periodeParam);
                        } else {
                            $btnStyle = "background-color:#ffffff; color:{$color}; border:1px solid {$color}";
                            $url = route('laporan.umur-piutang', array_merge($key === 'semua' ? [] : ['bucket' => $key], $periodeParam));
                        }
                    @endphp
                    <a href="{{ $url }}" style="{{ $btnStyle }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded">
                        {{ $label }} ({{ $count }})
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Filter Periode --}}
        <div>
            <p class="text-xs text-ink-muted font-medium mb-2">Periode:</p>
            <div class="flex flex-wrap gap-2">
                @foreach (['semua' => 'Semua', 'minggu-ini' => 'Minggu Ini', 'bulan-ini' => 'Bulan Ini', 'tahun-ini' => 'Tahun Ini'] as $key => $label)
                    @php
                        $pIsActive = $periode === $key;
                        $pColor = '#0E6E66';
                        $bucketParam = $bucket !== 'semua' ? ['bucket' => $bucket] : [];
                        if ($pIsActive) {
                            $pBtnStyle = "background-color:{$pColor}; color:#ffffff; border:2px solid {$pColor}";
                            $pUrl = route('laporan.umur-piutang', $bucketParam);
                        } else {
                            $pBtnStyle = "background-color:#ffffff; color:{$pColor}; border:1px solid {$pColor}";
                            $pUrl = route('laporan.umur-piutang', array_merge($key === 'semua' ? [] : ['periode' => $key], $bucketParam));
                        }
                    @endphp
                    <a href="{{ $pUrl }}" style="{{ $pBtnStyle }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Filter Dana & Sales --}}
        <div>
            <p class="text-xs text-ink-muted font-medium mb-2">Dana &amp; Sales:</p>
            <form method="GET" action="{{ route('laporan.umur-piutang') }}" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap">
                <input type="hidden" name="bucket" value="{{ $bucket }}">
                <input type="hidden" name="periode" value="{{ $periode }}">
                <div style="display:flex; align-items:center; gap:6px">
                    <label style="font-size:12px; color:#5B6470">Dana:</label>
                    <select name="sumber_dana"
                            style="width:140px; padding:6px 10px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:13px; color:#1B2027; outline-color:#0E6E66">
                        <option value="semua" {{ $sumber_dana === 'semua' ? 'selected' : '' }}>Semua</option>
                        @foreach($daftarSumber as $s)
                            <option value="{{ $s }}" {{ $sumber_dana === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex; align-items:center; gap:6px">
                    <label style="font-size:12px; color:#5B6470">Sales:</label>
                    <select name="sales"
                            style="width:180px; padding:6px 10px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:13px; color:#1B2027; outline-color:#0E6E66">
                        <option value="semua" {{ $sales === 'semua' ? 'selected' : '' }}>Semua</option>
                        @foreach($daftarSales as $sl)
                            <option value="{{ $sl }}" {{ $sales === $sl ? 'selected' : '' }}>{{ $sl }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                        style="padding:6px 16px; background:#0E6E66; color:white; border:none; border-radius:6px; font-size:13px; cursor:pointer; font-family:'IBM Plex Sans',sans-serif; font-weight:500">
                    Terapkan
                </button>
                @if($sumber_dana !== 'semua' || $sales !== 'semua')
                    <a href="{{ route('laporan.umur-piutang', array_filter(['bucket' => $bucket, 'periode' => $periode])) }}"
                       style="padding:6px 14px; border:1px solid #DCE2E0; border-radius:6px; font-size:13px; color:#5B6470; font-family:Inter,sans-serif; text-decoration:none">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- Info Hasil Filter --}}
        @php
            $bucketLabelMap = [
                'lancar' => 'Lancar',
                '0-30' => '0–30 Hari',
                '31-60' => '31–60 Hari',
                '>60' => '>60 Hari',
            ];
            $periodeLabelMap = [
                'minggu-ini' => 'Minggu Ini',
                'bulan-ini' => 'Bulan Ini',
                'tahun-ini' => 'Tahun Ini',
            ];
            $infoParts = ['Menampilkan', $totalCount, 'tagihan'];
            if ($bucket !== 'semua') {
                $infoParts[] = $bucketLabelMap[$bucket] ?? $bucket;
            }
            $infoPeriode = $periodeLabelMap[$periode] ?? null;
        @endphp
        <div style="background-color:#F6F7F6; border:1px solid #DCE2E0" class="rounded-lg px-4 py-3 flex items-start gap-2">
            <svg class="w-4 h-4 text-ink-muted mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" />
            </svg>
            <p class="text-sm text-ink-muted">
                {{ implode(' ', $infoParts) }}
                @if ($infoPeriode)
                    &middot; {{ $infoPeriode }}
                @endif
            </p>
        </div>

        {{-- Data Sections --}}
        @foreach ($bucketKeys as $key)
            @php
                $items = $paginatedTagihan ?? ($buckets[$key] ?? collect());
                $totalBucket = $summary[$key]['total'] ?? 0;
                $countBucket = $summary[$key]['count'] ?? 0;
                $sectionLabels = [
                    'lancar' => 'Lancar (Belum Jatuh Tempo)',
                    '0-30' => '0–30 Hari',
                    '31-60' => '31–60 Hari',
                    '>60' => '>60 Hari',
                ];
                $label = $sectionLabels[$key] ?? $key;
                $railClass = $key === 'lancar' ? 'aging-rail-lancar' : ($key === '0-30' ? 'aging-rail-watch30' : ($key === '31-60' ? 'aging-rail-watch60' : 'aging-rail-critical'));
                $badgeClass = $key === 'lancar' ? 'badge-lancar' : ($key === '0-30' ? 'badge-watch30' : ($key === '31-60' ? 'badge-watch60' : 'badge-critical'));
            @endphp

            <div class="bg-surface border border-line {{ $railClass }} rounded overflow-hidden">
                <div class="px-4 py-3 border-b border-line flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="{{ $badgeClass }} text-sm font-medium">{{ $label }}</span>
                        <span class="text-xs text-ink-muted">
                            {{ $countBucket }} tagihan
                            @if($countBucket > 0)
                                · Rp {{ App\Support\RupiahCompact::format($totalBucket) }}
                            @endif
                        </span>
                    </div>
                    <span class="rupiah text-sm font-medium text-ink">Rp {{ number_format($totalBucket, 2, ',', '.') }}</span>
                </div>

                @if ($items->isEmpty())
                    <div class="px-4 py-6 text-center text-sm text-ink-muted">
                        Tidak ada tagihan di bucket ini.
                    </div>
                @else
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="w-full" style="min-width:1000px">
                            <thead>
                                <tr class="border-b border-line">
                                    <th class="table-header">Invoice</th>
                                    <th class="table-header">No. SJ</th>
                                    <th class="table-header">Lembaga</th>
                                    <th class="table-header">Kabupaten</th>
                                    <th class="table-header">Sales</th>
                                    <th class="table-header">Dana</th>
                                    <th class="table-header">JT</th>
                                    <th class="table-header text-right">Hari Lewat</th>
                                    <th class="table-header text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $t)
                                    <tr class="border-b border-line hover:bg-paper transition {{ $railClass }}">
                                        <td class="table-cell">
                                            @if(Auth::user()->isAdministrasi())
                                                <a href="{{ route('tagihan.show', $t) }}" class="text-action hover:underline font-mono font-medium">
                                                    {{ $t->no_invoice }}
                                                </a>
                                            @else
                                                <span class="font-mono text-ink">{{ $t->no_invoice }}</span>
                                            @endif
                                        </td>
                                        <td class="table-cell" style="font-size:11px; font-family:'IBM Plex Mono',monospace; color:#5B6470">
                                            {{ $t->no_sj ?: '-' }}
                                        </td>
                                        <td class="table-cell">
                                            @if(Auth::user()->isAdministrasi())
                                                <a href="{{ route('pelanggan.show', $t->pelanggan) }}" class="text-action hover:underline">
                                                    {{ $t->pelanggan->nama_lembaga ?: $t->pelanggan->nama_pelanggan }}
                                                </a>
                                            @else
                                                <span class="text-ink">{{ $t->pelanggan->nama_lembaga ?: $t->pelanggan->nama_pelanggan }}</span>
                                            @endif
                                        </td>
                                        <td class="table-cell">
                                            {{ $t->pelanggan->kabupaten ?: '-' }}
                                        </td>
                                        <td class="table-cell" style="font-size:12px; color:#5B6470">
                                            {{ $t->nama_sales ?: '-' }}
                                        </td>
                                        <td class="table-cell">
                                            <x-badge-sumber-dana :sumber="$t->sumber_dana" />
                                        </td>
                                        <td class="table-cell font-mono">{{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}</td>
                                        <td class="table-cell text-right font-mono">{{ $t->days_overdue }}</td>
                                        <td class="table-cell rupiah">Rp {{ number_format($t->total_tagihan, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="sm:hidden divide-y divide-line">
                        @foreach ($items as $t)
                            <div class="p-4 {{ $railClass }} space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    @if(Auth::user()->isAdministrasi())
                                        <a href="{{ route('tagihan.show', $t) }}" class="text-action hover:underline font-mono font-medium">
                                            {{ $t->no_invoice }}
                                        </a>
                                    @else
                                        <span class="font-mono text-ink">{{ $t->no_invoice }}</span>
                                    @endif
                                    <span class="rupiah text-ink-muted">Rp {{ number_format($t->total_tagihan, 2, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    @if(Auth::user()->isAdministrasi())
                                        <a href="{{ route('pelanggan.show', $t->pelanggan) }}" class="text-action hover:underline">
                                            {{ $t->pelanggan->nama_lembaga ?: $t->pelanggan->nama_pelanggan }}
                                        </a>
                                    @else
                                        <span class="text-ink">{{ $t->pelanggan->nama_lembaga ?: $t->pelanggan->nama_pelanggan }}</span>
                                    @endif
                                    <span class="text-ink-muted">{{ $t->days_overdue }} hari lewat</span>
                                </div>
                                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px" class="text-xs text-ink-muted">
                                    <span>
                                        <span style="font-family:'IBM Plex Mono',monospace">{{ $t->no_sj ?: '-' }}</span>
                                        @if($t->nama_sales)
                                            · {{ $t->nama_sales }}
                                        @endif
                                    </span>
                                    <span style="display:inline-flex; align-items:center; gap:6px">
                                        <x-badge-sumber-dana :sumber="$t->sumber_dana" />
                                    </span>
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

        @if ($paginatedTagihan)
            <div class="mt-4">
                {{ $paginatedTagihan->links() }}
            </div>
        @endif

        <div class="bg-surface border border-line rounded p-4">
            <div class="flex items-center justify-between text-sm">
                <span class="font-medium text-ink">Total Piutang Belum Lunas</span>
                <span class="rupiah font-semibold text-ink text-lg">Rp {{ number_format(array_sum(array_column($summary, 'total')), 2, ',', '.') }}</span>
            </div>
        </div>
    </div>
</x-app-layout>
