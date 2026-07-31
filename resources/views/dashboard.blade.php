<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    @if (Auth::user()->isAdministrasi())
        <div style="display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap">
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Tagihan Belum Lunas
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
                    {{ $tagihanBelumLunas }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Jatuh Tempo Minggu Ini
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#B33A2E; font-weight:600">
                    {{ $tagihanJatuhTempoMingguIni }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Total Piutang
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
                    Rp {{ number_format($totalPiutang, 0, ',', '.') }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Pelanggan Aktif
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
                    {{ $totalPelangganAktif }}
                </div>
            </div>
        </div>

        @php
            $bucketMeta = [
                'lancar' => ['label' => 'Lancar', 'color' => '#6B7CA3'],
                '0-30' => ['label' => '0-30 Hari', 'color' => '#C8862A'],
                '31-60' => ['label' => '31-60 Hari', 'color' => '#B8612A'],
                '>60' => ['label' => '>60 Hari', 'color' => '#B33A2E'],
            ];
            $maxCount = max(array_column($agingSummary, 'count')) ?: 1;
        @endphp

        <div style="border:1px solid #DCE2E0; border-radius:8px; padding:16px; margin-bottom:16px">
            <h2 style="font-size:15px; font-weight:600; color:#1B2027; margin:0 0 12px 0">Kondisi Piutang Saat Ini</h2>
            @foreach($bucketMeta as $key => $meta)
                @php
                    $data = $agingSummary[$key] ?? ['count' => 0, 'total' => 0];
                @endphp
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px">
                    <span style="width:80px; font-size:12px; color:#5B6470">{{ $meta['label'] }}</span>
                    <div style="flex:1; height:6px; background:#F0F0F0; border-radius:3px; overflow:hidden">
                        <div style="height:100%; border-radius:3px; width:{{ ($data['count'] / $maxCount) * 100 }}%; background:{{ $meta['color'] }}"></div>
                    </div>
                    <span style="width:80px; font-size:12px; color:#5B6470; font-family:'IBM Plex Mono',monospace">{{ $data['count'] }} tagihan</span>
                    <span style="width:120px; font-size:12px; text-align:right; color:#1B2027; font-family:'IBM Plex Mono',monospace">Rp {{ number_format($data['total'], 0, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        <div class="bg-surface border border-line rounded overflow-hidden">
            <div class="px-4 py-3 border-b border-line">
                <h2 class="font-display text-lg font-semibold text-ink">Tagihan Terbaru</h2>
            </div>

            <div class="hidden sm:block overflow-x-auto">
                <table style="width:100%; table-layout:fixed">
                    <thead>
                        <tr class="border-b border-line">
                            <th style="width:22%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Invoice</th>
                            <th style="width:28%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Pelanggan</th>
                            <th style="width:15%; padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Total</th>
                            <th style="width:17%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Status</th>
                            <th style="width:18%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Jatuh Tempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tagihanTerbaru as $t)
                            @php
                                $rail = match(true) {
                                    $t->status === 'lunas' => '#3E7C58',
                                    !$t->tanggal_jatuh_tempo->isPast() => '#6B7CA3',
                                    $t->days_overdue <= 30 => '#C8862A',
                                    $t->days_overdue <= 60 => '#B8612A',
                                    default => '#B33A2E',
                                };

                                [$label, $color] = match(true) {
                                    $t->status === 'lunas' => ['Lunas', '#3E7C58'],
                                    $t->tanggal_jatuh_tempo->isPast() => ['Jatuh Tempo', '#B33A2E'],
                                    default => ['Belum Lunas', '#C8862A'],
                                };
                                $bg = $color . '20';
                            @endphp
                            <tr class="border-b border-line hover:bg-paper transition" style="border-left:3px solid {{ $rail }}">
                                <td style="padding:12px 16px; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0"
                                    title="{{ $t->no_invoice }}">
                                    <a href="{{ route('tagihan.show', $t) }}"
                                       style="color:#0E6E66; text-decoration:none; font-family:'IBM Plex Mono',monospace; font-weight:500">
                                        {{ $t->no_invoice }}
                                    </a>
                                </td>
                                <td style="padding:12px 16px; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0"
                                    title="{{ $t->pelanggan->nama_pelanggan }}">
                                    {{ $t->pelanggan->nama_pelanggan }}
                                </td>
                                <td style="padding:12px 16px; font-size:14px; white-space:nowrap; text-align:right; font-family:'IBM Plex Mono',monospace">
                                    Rp {{ number_format($t->total_tagihan, 0, ',', '.') }}
                                </td>
                                <td style="padding:12px 16px; white-space:nowrap">
                                    <span style="font-size:11px; padding:2px 8px; border-radius:4px; white-space:nowrap; background:{{ $bg }}; color:{{ $color }}; border:1px solid {{ $color }}">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td style="padding:12px 16px; font-size:14px; font-family:'IBM Plex Mono',monospace">
                                    {{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding:32px; text-align:center; color:#5B6470; font-size:14px">
                                    Belum ada tagihan.
                                    @can('create', App\Models\Tagihan::class)
                                        <a href="{{ route('tagihan.create') }}" style="color:#0E6E66; text-decoration:none">Buat tagihan baru</a>
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
                        $rail = match(true) {
                            $t->status === 'lunas' => '#3E7C58',
                            !$t->tanggal_jatuh_tempo->isPast() => '#6B7CA3',
                            $t->days_overdue <= 30 => '#C8862A',
                            $t->days_overdue <= 60 => '#B8612A',
                            default => '#B33A2E',
                        };

                        [$label, $color] = match(true) {
                            $t->status === 'lunas' => ['Lunas', '#3E7C58'],
                            $t->tanggal_jatuh_tempo->isPast() => ['Jatuh Tempo', '#B33A2E'],
                            default => ['Belum Lunas', '#C8862A'],
                        };
                        $bg = $color . '20';
                    @endphp
                    <div class="p-4 space-y-2" style="border-left:3px solid {{ $rail }}">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('tagihan.show', $t) }}"
                               style="color:#0E6E66; text-decoration:none; font-family:'IBM Plex Mono',monospace; font-weight:500; font-size:14px">
                                {{ $t->no_invoice }}
                            </a>
                            <span style="font-size:11px; padding:2px 8px; border-radius:4px; white-space:nowrap; background:{{ $bg }}; color:{{ $color }}; border:1px solid {{ $color }}">
                                {{ $label }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span>{{ $t->pelanggan->nama_pelanggan }}</span>
                            <span style="font-family:'IBM Plex Mono',monospace">Rp {{ number_format($t->total_tagihan, 0, ',', '.') }}</span>
                        </div>
                        <div class="text-xs" style="color:#5B6470">
                            Jatuh tempo: {{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center" style="color:#5B6470; font-size:14px">
                        Belum ada tagihan.
                        @can('create', App\Models\Tagihan::class)
                            <a href="{{ route('tagihan.create') }}" style="color:#0E6E66; text-decoration:none">Buat tagihan baru</a>
                        @endcan
                    </div>
                @endforelse
            </div>

            <div style="text-align:right; padding:12px 16px; border-top:1px solid #DCE2E0">
                <a href="{{ route('tagihan.index') }}"
                   style="font-size:13px; color:#0E6E66; text-decoration:none; font-weight:500">
                    Lihat semua tagihan →
                </a>
            </div>
        </div>

        @if($jatuhTempoMingguIni->count() > 0)
            <div style="margin-top:24px; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <h2 style="font-size:15px; font-weight:600; color:#1B2027; margin:0 0 12px 0">⚠ Jatuh Tempo Minggu Ini</h2>
                <table style="width:100%; table-layout:fixed">
                    <thead>
                        <tr class="border-b border-line">
                            <th style="width:20%; padding:8px 12px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">No. Invoice</th>
                            <th style="width:40%; padding:8px 12px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Pelanggan</th>
                            <th style="width:20%; padding:8px 12px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Jatuh Tempo</th>
                            <th style="width:20%; padding:8px 12px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jatuhTempoMingguIni as $t)
                            @php
                                $jarakHari = $t->tanggal_jatuh_tempo->startOfDay()->diffInDays(now()->startOfDay(), false);
                                $dueColor = $jarakHari <= 3 ? '#C8862A' : '#1B2027';
                            @endphp
                            <tr class="border-b border-line">
                                <td style="padding:8px 12px; font-size:13px; font-family:'IBM Plex Mono',monospace">
                                    <a href="{{ route('tagihan.show', $t) }}"
                                       style="color:#0E6E66; text-decoration:none; font-weight:500">
                                        {{ $t->no_invoice }}
                                    </a>
                                </td>
                                <td style="padding:8px 12px; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0"
                                    title="{{ $t->pelanggan->nama_pelanggan }}">
                                    {{ $t->pelanggan->nama_pelanggan }}
                                </td>
                                <td style="padding:8px 12px; font-size:14px; font-family:'IBM Plex Mono',monospace; color:{{ $dueColor }}">
                                    {{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}
                                </td>
                                <td style="padding:8px 12px; font-size:14px; font-family:'IBM Plex Mono',monospace; text-align:right; white-space:nowrap">
                                    Rp {{ number_format($t->total_tagihan, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @else
        <div style="display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap">
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Total Piutang
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
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
                    Piutang Belum Tertagih
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#B33A2E; font-weight:600">
                    Rp {{ number_format(max(0, $totalPiutang - $totalTertagih), 0, ',', '.') }}
                </div>
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
                        <span class="rupiah text-sm font-medium text-ink">Rp {{ number_format($item['total'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-app-layout>
