<x-app-layout>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
        <h1 class="font-display text-2xl font-semibold text-ink">Laporan Rekapitulasi</h1>
        <a href="{{ route('laporan.rekapitulasi.export', request()->only('search', 'wilayah', 'kabupaten')) }}"
           style="background:#0E6E66; color:white; padding:8px 16px; border-radius:6px; font-size:14px; text-decoration:none; font-family:'IBM Plex Sans',sans-serif; font-weight:500">
            Export Excel
        </a>
    </div>

    <form method="GET" action="{{ route('laporan.rekapitulasi') }}">
        <div style="display:flex; gap:8px; align-items:center; margin-bottom:16px">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari nama pelanggan..."
                autocomplete="off"
                style="flex:1; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66; box-sizing:border-box">

            <select name="wilayah"
                    style="width:180px; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
                <option value="semua" {{ $wilayah === 'semua' ? 'selected' : '' }}>Semua Wilayah</option>
                @foreach($daftarWilayah as $w)
                    <option value="{{ $w }}" {{ $wilayah === $w ? 'selected' : '' }}>{{ $w }}</option>
                @endforeach
            </select>

            <select name="kabupaten"
                    style="width:180px; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
                <option value="semua" {{ $kabupaten === 'semua' ? 'selected' : '' }}>Semua Kabupaten</option>
                @foreach($daftarKabupaten as $k)
                    <option value="{{ $k }}" {{ $kabupaten === $k ? 'selected' : '' }}>{{ $k }}</option>
                @endforeach
            </select>

            <button type="submit"
                    style="padding:8px 20px; background:#0E6E66; color:white; border:none; border-radius:6px; font-size:14px; cursor:pointer; font-family:'IBM Plex Sans',sans-serif; font-weight:500">
                Filter
            </button>

            @if($search || $wilayah !== 'semua' || $kabupaten !== 'semua')
                <a href="{{ route('laporan.rekapitulasi') }}"
                   style="padding:8px 16px; border:1px solid #DCE2E0; border-radius:6px; font-size:14px; color:#5B6470; font-family:Inter,sans-serif; text-decoration:none">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <p style="font-size:13px; color:#5B6470; margin-bottom:8px">
        @if($search || $wilayah !== 'semua' || $kabupaten !== 'semua')
            Menampilkan {{ $paginated->total() }} dari {{ $totalSemua }} pelanggan
            @if($search) untuk "{{ $search }}"@endif
            @if($wilayah !== 'semua') · Wilayah: {{ $wilayah }}@endif
            @if($kabupaten !== 'semua') · Kabupaten: {{ $kabupaten }}@endif
        @else
            {{ $totalSemua }} pelanggan
        @endif
    </p>

    <div style="display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap">
        <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                Total Piutang
            </div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#B33A2E; font-weight:600">
                Rp {{ number_format($totalPiutang, 0, ',', '.') }}
            </div>
        </div>
        <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                Total Tertagih
            </div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#3E7C58; font-weight:600">
                Rp {{ number_format($totalTertagih, 0, ',', '.') }}
            </div>
        </div>
        <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                Sisa Piutang
            </div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#B33A2E; font-weight:600">
                Rp {{ number_format($totalSisa, 0, ',', '.') }}
            </div>
        </div>
        <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                Total Pelanggan
            </div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
                {{ $totalPelanggan }}
            </div>
        </div>
    </div>

    <div class="bg-surface border border-line rounded overflow-hidden">
        <div class="hidden sm:block overflow-x-auto">
            <table style="width:100%; min-width:1100px; table-layout:fixed">
                <thead>
                    <tr class="border-b border-line">
                        <th style="width:4%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">No</th>
                        <th style="width:16%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Pelanggan</th>
                        <th style="width:14%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Lembaga</th>
                        <th style="width:10%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Kabupaten</th>
                        <th style="width:9%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Sumber Dana</th>
                        <th style="width:12%; padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Total Tagihan</th>
                        <th style="width:12%; padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Total Terbayar</th>
                        <th style="width:13%; padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Sisa Piutang</th>
                        <th style="width:10%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paginated as $r)
                        @php
                            $sisaLunas = $r->sisa_piutang <= 0;
                            $sisaColor = $sisaLunas ? '#3E7C58' : '#B33A2E';

                            $bucketMap = [
                                'lancar' => ['Lancar', '#6B7CA3'],
                                '0-30' => ['0-30 Hari', '#C8862A'],
                                '31-60' => ['31-60 Hari', '#B8612A'],
                                '>60' => ['>60 Hari', '#B33A2E'],
                            ];
                            $worst = $r->bucket_terburuk;
                        @endphp
                        <tr class="border-b border-line hover:bg-paper transition">
                            <td style="padding:12px 16px; font-size:14px; font-family:'IBM Plex Mono',monospace">
                                {{ ($paginated->currentPage() - 1) * $paginated->perPage() + $loop->iteration }}
                            </td>
                            <td style="padding:12px 16px; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0"
                                title="{{ $r->pelanggan->nama_pelanggan }}">
                                @if(Auth::user()->isAdministrasi())
                                    <a href="{{ route('pelanggan.show', $r->pelanggan) }}"
                                       style="color:#0E6E66; text-decoration:none; font-weight:500">
                                        {{ $r->pelanggan->nama_pelanggan }}
                                    </a>
                                @else
                                    <span style="font-weight:500; color:#1B2027">{{ $r->pelanggan->nama_pelanggan }}</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0"
                                title="{{ $r->pelanggan->nama_lembaga ?: '-' }}">
                                <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
                                    {{ $r->pelanggan->nama_lembaga ?: '-' }}
                                </div>
                                @if($r->pelanggan->status_lembaga)
                                    <div style="font-size:10px; color:#8A929C">
                                        {{ $r->pelanggan->status_lembaga }}
                                    </div>
                                @endif
                            </td>
                            <td style="padding:12px 16px; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0"
                                title="{{ $r->pelanggan->kabupaten ?: $r->pelanggan->wilayah }}">
                                {{ $r->pelanggan->kabupaten ?: $r->pelanggan->wilayah }}
                            </td>
                            <td style="padding:12px 16px; white-space:nowrap">
                                @php
                                    $sumberDominan = $r->pelanggan->tagihan->where('approval_status', 'aktif')
                                        ->groupBy('sumber_dana')
                                        ->sortByDesc(fn ($g) => $g->count())
                                        ->keys()->first();
                                @endphp
                                @if($sumberDominan)
                                    <x-badge-sumber-dana :sumber="$sumberDominan" />
                                @endif
                            </td>
                            <td style="padding:12px 16px; font-size:14px; white-space:nowrap; text-align:right; font-family:'IBM Plex Mono',monospace">
                                Rp {{ number_format($r->total_tagihan, 2, ',', '.') }}
                            </td>
                            <td style="padding:12px 16px; font-size:14px; white-space:nowrap; text-align:right; font-family:'IBM Plex Mono',monospace">
                                Rp {{ number_format($r->total_terbayar, 2, ',', '.') }}
                            </td>
                            <td style="padding:12px 16px; text-align:right; white-space:nowrap">
                                <div style="font-family:'IBM Plex Mono',monospace; font-size:14px; color:{{ $sisaColor }}">
                                    Rp {{ number_format($r->sisa_piutang, 2, ',', '.') }}
                                </div>
                                @if($sisaLunas)
                                    <span style="font-size:10px; padding:1px 6px; border-radius:3px; background:#3E7C5820; color:#3E7C58; border:1px solid #3E7C58">Lunas</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; white-space:nowrap">
                                @if ($worst)
                                    @php [$bLabel, $bColor] = $bucketMap[$worst] ?? ['-', '#5B6470']; @endphp
                                    <span style="font-size:11px; padding:2px 8px; border-radius:4px; white-space:nowrap; background:{{ $bColor }}20; color:{{ $bColor }}; border:1px solid {{ $bColor }}">
                                        {{ $bLabel }}
                                    </span>
                                @else
                                    <span style="font-size:11px; padding:2px 8px; border-radius:4px; white-space:nowrap; background:#3E7C5820; color:#3E7C58; border:1px solid #3E7C58">
                                        Lunas
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding:32px; text-align:center; color:#5B6470; font-size:14px">
                                @if($search || $wilayah !== 'semua' || $kabupaten !== 'semua')
                                    Tidak ada pelanggan yang cocok dengan filter ini.
                                    <a href="{{ route('laporan.rekapitulasi') }}" style="color:#0E6E66; text-decoration:none">Reset filter</a>
                                @else
                                    Belum ada data piutang untuk ditampilkan.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sm:hidden divide-y divide-line">
            @forelse ($paginated as $r)
                @php
                    $sisaLunas = $r->sisa_piutang <= 0;
                    $sisaColor = $sisaLunas ? '#3E7C58' : '#B33A2E';

                    $bucketMap = [
                        'lancar' => ['Lancar', '#6B7CA3'],
                        '0-30' => ['0-30 Hari', '#C8862A'],
                        '31-60' => ['31-60 Hari', '#B8612A'],
                        '>60' => ['>60 Hari', '#B33A2E'],
                    ];
                    $worst = $r->bucket_terburuk;
                @endphp
                <div class="p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        @if(Auth::user()->isAdministrasi())
                            <a href="{{ route('pelanggan.show', $r->pelanggan) }}"
                               style="color:#0E6E66; text-decoration:none; font-weight:500; font-size:14px">
                                {{ $r->pelanggan->nama_pelanggan }}
                            </a>
                        @else
                            <span style="font-weight:500; color:#1B2027; font-size:14px">{{ $r->pelanggan->nama_pelanggan }}</span>
                        @endif
                        @if ($worst)
                            @php [$bLabel, $bColor] = $bucketMap[$worst] ?? ['-', '#5B6470']; @endphp
                            <span style="font-size:11px; padding:2px 8px; border-radius:4px; white-space:nowrap; background:{{ $bColor }}20; color:{{ $bColor }}; border:1px solid {{ $bColor }}">
                                {{ $bLabel }}
                            </span>
                        @else
                            <span style="font-size:11px; padding:2px 8px; border-radius:4px; white-space:nowrap; background:#3E7C5820; color:#3E7C58; border:1px solid #3E7C58">
                                Lunas
                            </span>
                        @endif
                    </div>
                    @php
                        $sumberDominan = $r->pelanggan->tagihan->where('approval_status', 'aktif')
                            ->groupBy('sumber_dana')
                            ->sortByDesc(fn ($g) => $g->count())
                            ->keys()->first();
                    @endphp
                    @if($r->pelanggan->nama_lembaga || $r->pelanggan->kabupaten || $sumberDominan)
                        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px" class="text-xs text-ink-muted">
                            <span>
                                {{ $r->pelanggan->nama_lembaga ?: '-' }}
                                @if($r->pelanggan->status_lembaga)
                                    ({{ $r->pelanggan->status_lembaga }})
                                @endif
                                · {{ $r->pelanggan->kabupaten ?: $r->pelanggan->wilayah }}
                            </span>
                            @if($sumberDominan)
                                <x-badge-sumber-dana :sumber="$sumberDominan" />
                            @endif
                        </div>
                    @endif
                    <div class="flex items-center justify-between text-sm">
                        <span style="color:#5B6470">{{ $r->pelanggan->wilayah ?: '-' }}</span>
                        <span style="font-family:'IBM Plex Mono',monospace; font-weight:500; color:{{ $sisaColor }}">
                            Rp {{ number_format($r->sisa_piutang, 2, ',', '.') }}
                            @if($sisaLunas)
                                <span style="font-size:10px; padding:1px 6px; border-radius:3px; background:#3E7C5820; color:#3E7C58; border:1px solid #3E7C58">Lunas</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs" style="color:#5B6470">
                        <span>Tagihan: Rp {{ number_format($r->total_tagihan, 2, ',', '.') }}</span>
                        <span>Terbayar: Rp {{ number_format($r->total_terbayar, 2, ',', '.') }}</span>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center" style="color:#5B6470; font-size:14px">
                    @if($search || $wilayah !== 'semua' || $kabupaten !== 'semua')
                        Tidak ada pelanggan yang cocok dengan filter ini.
                        <a href="{{ route('laporan.rekapitulasi') }}" style="color:#0E6E66; text-decoration:none">Reset filter</a>
                    @else
                        Belum ada data piutang untuk ditampilkan.
                    @endif
                </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-line hidden sm:block">
            {{ $paginated->links() }}
        </div>
    </div>

    @if (count($chartLabels) > 0)
        <div class="bg-surface border border-line rounded p-6" style="margin-top:16px">
            <h2 class="font-display text-lg font-semibold text-ink mb-4">Grafik Sisa Piutang per Pelanggan (Top 10)</h2>
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
