<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    @if (Auth::user()->isPimpinan())
        {{-- ==== LAYOUT PIMPINAN ==== --}}

        {{-- Baris 1 — 4 KPI monitoring approval --}}
        <div style="display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap">
            <div style="flex:1; border:1px solid #E5C99B; border-radius:8px; padding:16px; background:#FFFBF5">
                <div style="font-size:11px; color:#C8862A; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Menunggu Persetujuan
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#B8612A; font-weight:600">
                    {{ $totalMenunggu }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #E5C99B; border-radius:8px; padding:16px; background:#FFFBF5">
                <div style="font-size:11px; color:#C8862A; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Nilai Menunggu Approval
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#B8612A; font-weight:600">
                    Rp {{ number_format((float) $nilaiMenunggu, 0, ',', '.') }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #BFD8C8; border-radius:8px; padding:16px; background:#F6FBF8">
                <div style="font-size:11px; color:#3E7C58; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Disetujui Hari Ini
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#3E7C58; font-weight:600">
                    {{ $disetujuiHariIni }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #E8C0BA; border-radius:8px; padding:16px; background:#FFF7F6">
                <div style="font-size:11px; color:#B33A2E; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Ditolak Hari Ini
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#B33A2E; font-weight:600">
                    {{ $ditolakHariIni }}
                </div>
            </div>
        </div>

        {{-- Baris 2 — 3 KPI kondisi piutang --}}
        <div style="display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap">
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Total Belum Lunas
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
                    {{ $totalBelumLunas }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Total Piutang
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
                    Rp {{ number_format((float) $totalPiutang, 0, ',', '.') }}
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

        {{-- Baris 3 — dua kolom: menunggu approval + top piutang --}}
        <div style="display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap">
            {{-- Kolom kiri (60%): tagihan menunggu persetujuan --}}
            <div style="flex:3; min-width:320px; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
                    <h2 style="font-size:15px; font-weight:600; color:#1B2027; margin:0">⏳ Menunggu Persetujuan ({{ $totalMenunggu }})</h2>
                    <a href="{{ route('approval.index') }}" style="font-size:13px; color:#0E6E66; text-decoration:none; font-weight:500">
                        Lihat semua →
                    </a>
                </div>

                @forelse ($menungguApproval as $t)
                    <div style="display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid #EEF0EF">
                        <div style="flex:1.4; min-width:0">
                            <a href="{{ route('tagihan.show', $t) }}" style="color:#0E6E66; text-decoration:none; font-family:'IBM Plex Mono',monospace; font-weight:500; font-size:13px">
                                {{ $t->no_invoice }}
                            </a>
                            <div style="font-size:12px; color:#5B6470; white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
                                {{ $t->pelanggan->nama_pelanggan }}
                            </div>
                        </div>
                        <div style="flex:1; font-size:13px; font-family:'IBM Plex Mono',monospace; text-align:right; color:#1B2027; white-space:nowrap">
                            Rp {{ number_format((float) $t->total_tagihan, 0, ',', '.') }}
                        </div>
                        <div style="display:flex; gap:6px; flex-shrink:0">
                            <form method="POST" action="{{ route('approval.setujui', $t) }}" onsubmit="return confirm('Setujui tagihan {{ $t->no_invoice }}?')">
                                @csrf
                                <button type="submit" title="Setujui"
                                        style="width:30px; height:30px; border-radius:6px; border:1px solid #3E7C58; background:#F6FBF8; color:#3E7C58; font-weight:700; font-size:14px; cursor:pointer; line-height:1">
                                    ✓
                                </button>
                            </form>
                            <form method="POST" action="{{ route('approval.tolak', $t) }}"
                                  onsubmit="var r=prompt('Alasan penolakan untuk {{ $t->no_invoice }} (minimal 10 karakter):'); if(r===null){return false;} if(r.trim().length<10){alert('Alasan minimal 10 karakter.'); return false;} this.approval_note.value=r.trim(); return true;">
                                @csrf
                                <input type="hidden" name="approval_note" value="">
                                <button type="submit" title="Tolak"
                                        style="width:30px; height:30px; border-radius:6px; border:1px solid #B33A2E; background:#FFF7F6; color:#B33A2E; font-weight:700; font-size:14px; cursor:pointer; line-height:1">
                                    ✕
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p style="font-size:13px; color:#5B6470; margin:8px 0 0">Tidak ada tagihan yang menunggu persetujuan.</p>
                @endforelse
            </div>

            {{-- Kolom kanan (40%): top 5 piutang terbesar --}}
            <div style="flex:2; min-width:260px; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <h2 style="font-size:15px; font-weight:600; color:#1B2027; margin:0 0 12px 0">Top 5 Piutang Terbesar</h2>
                @forelse ($topPiutang as $i => $p)
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #EEF0EF">
                        <div style="display:flex; align-items:center; gap:10px; min-width:0">
                            <span style="width:20px; height:20px; border-radius:50%; background:#EEF0EF; color:#5B6470; font-size:11px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0">
                                {{ $i + 1 }}
                            </span>
                            <span style="font-size:13px; color:#1B2027; white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
                                {{ $p->nama_pelanggan }}
                            </span>
                        </div>
                        <span style="font-size:13px; font-family:'IBM Plex Mono',monospace; color:#1B2027; white-space:nowrap">
                            Rp {{ number_format((float) $p->total_piutang, 0, ',', '.') }}
                        </span>
                    </div>
                @empty
                    <p style="font-size:13px; color:#5B6470; margin:8px 0 0">Belum ada data piutang.</p>
                @endforelse
            </div>
        </div>

        {{-- Baris 4 — progress bar aging --}}
        @php
            $bucketMeta = [
                'lancar' => ['label' => 'Lancar', 'color' => '#6B7CA3'],
                '0-30' => ['label' => '0-30 Hari', 'color' => '#C8862A'],
                '31-60' => ['label' => '31-60 Hari', 'color' => '#B8612A'],
                '>60' => ['label' => '>60 Hari', 'color' => '#B33A2E'],
            ];
            $maxCount = max(array_column($agingSummary, 'count')) ?: 1;
        @endphp

        <div style="border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <h2 style="font-size:15px; font-weight:600; color:#1B2027; margin:0 0 12px 0">Kondisi Piutang Saat Ini</h2>
            @foreach ($bucketMeta as $key => $meta)
                @php $data = $agingSummary[$key] ?? ['count' => 0, 'total' => 0]; @endphp
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px">
                    <span style="width:80px; font-size:12px; color:#5B6470">{{ $meta['label'] }}</span>
                    <div style="flex:1; height:6px; background:#F0F0F0; border-radius:3px; overflow:hidden">
                        <div style="height:100%; border-radius:3px; width:{{ ($data['count'] / $maxCount) * 100 }}%; background:{{ $meta['color'] }}"></div>
                    </div>
                    <span style="width:80px; font-size:12px; color:#5B6470; font-family:'IBM Plex Mono',monospace">{{ $data['count'] }} tagihan</span>
                    <span style="width:120px; font-size:12px; text-align:right; color:#1B2027; font-family:'IBM Plex Mono',monospace">Rp {{ number_format((float) $data['total'], 0, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        {{-- Monitoring Tim Sales --}}
        @if($monitoringSales->count() > 0)
        <div style="margin-top:24px; border:1px solid #DCE2E0;
                    border-radius:8px; padding:20px">
            <h3 style="margin:0 0 16px; font-size:16px; color:#1B2027">
                Monitoring Tim Sales
            </h3>
            <table style="width:100%; table-layout:fixed">
                <thead>
                    <tr>
                        <th style="text-align:left; font-size:11px; color:#5B6470">
                            NAMA SALES
                        </th>
                        <th style="text-align:center; font-size:11px; color:#5B6470">
                            TOTAL
                        </th>
                        <th style="text-align:center; font-size:11px; color:#C8862A">
                            SEDANG PROSES
                        </th>
                        <th style="text-align:center; font-size:11px; color:#5B6470">
                            BELUM DITAGIH
                        </th>
                        <th style="text-align:center; font-size:11px; color:#3E7C58">
                            SUDAH DITAGIH
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monitoringSales as $sales)
                    <tr>
                        <td>{{ $sales->name }}</td>
                        <td style="text-align:center">{{ $sales->total_assigned }}</td>
                        <td style="text-align:center; color:#C8862A; font-weight:500">
                            {{ $sales->sedang_proses }}
                        </td>
                        <td style="text-align:center; color:#5B6470">
                            {{ $sales->belum_ditagih }}
                        </td>
                        <td style="text-align:center; color:#3E7C58; font-weight:500">
                            {{ $sales->sudah_ditagih }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    @elseif (Auth::user()->isAdministrasi())
        {{-- ==== LAYOUT BAGIAN ADMINISTRASI ==== --}}

        <div style="display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap">
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Tagihan Belum Lunas
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
                    {{ $totalBelumLunas }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Jatuh Tempo Minggu Ini
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#B33A2E; font-weight:600">
                    {{ $jatuhTempoMingguIni }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Total Piutang
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
                    Rp {{ number_format((float) $totalPiutang, 0, ',', '.') }}
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
            @foreach ($bucketMeta as $key => $meta)
                @php $data = $agingSummary[$key] ?? ['count' => 0, 'total' => 0]; @endphp
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px">
                    <span style="width:80px; font-size:12px; color:#5B6470">{{ $meta['label'] }}</span>
                    <div style="flex:1; height:6px; background:#F0F0F0; border-radius:3px; overflow:hidden">
                        <div style="height:100%; border-radius:3px; width:{{ ($data['count'] / $maxCount) * 100 }}%; background:{{ $meta['color'] }}"></div>
                    </div>
                    <span style="width:80px; font-size:12px; color:#5B6470; font-family:'IBM Plex Mono',monospace">{{ $data['count'] }} tagihan</span>
                    <span style="width:120px; font-size:12px; text-align:right; color:#1B2027; font-family:'IBM Plex Mono',monospace">Rp {{ number_format((float) $data['total'], 0, ',', '.') }}</span>
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
                                $rail = match (true) {
                                    $t->status === 'lunas' => '#3E7C58',
                                    !$t->tanggal_jatuh_tempo->isPast() => '#6B7CA3',
                                    $t->days_overdue <= 30 => '#C8862A',
                                    $t->days_overdue <= 60 => '#B8612A',
                                    default => '#B33A2E',
                                };

                                [$label, $color] = match (true) {
                                    $t->status === 'lunas' => ['Lunas', '#3E7C58'],
                                    $t->tanggal_jatuh_tempo->isPast() => ['Jatuh Tempo', '#B33A2E'],
                                    default => ['Belum Lunas', '#C8862A'],
                                };
                                $bg = $color.'20';
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
                                    Rp {{ number_format((float) $t->total_tagihan, 0, ',', '.') }}
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
                        $rail = match (true) {
                            $t->status === 'lunas' => '#3E7C58',
                            !$t->tanggal_jatuh_tempo->isPast() => '#6B7CA3',
                            $t->days_overdue <= 30 => '#C8862A',
                            $t->days_overdue <= 60 => '#B8612A',
                            default => '#B33A2E',
                        };

                        [$label, $color] = match (true) {
                            $t->status === 'lunas' => ['Lunas', '#3E7C58'],
                            $t->tanggal_jatuh_tempo->isPast() => ['Jatuh Tempo', '#B33A2E'],
                            default => ['Belum Lunas', '#C8862A'],
                        };
                        $bg = $color.'20';
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
                            <span style="font-family:'IBM Plex Mono',monospace">Rp {{ number_format((float) $t->total_tagihan, 0, ',', '.') }}</span>
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

        @if ($jatuhTempoList->count() > 0)
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
                        @foreach ($jatuhTempoList as $t)
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
                                    Rp {{ number_format((float) $t->total_tagihan, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($tagihanDitolak->count() > 0)
            <div style="margin-top:24px; padding:16px; background:#FFF5F5; border:1px solid #B33A2E; border-radius:8px">
                <h3 style="color:#B33A2E; margin:0 0 12px; font-size:15px">
                    ✕ Tagihan Ditolak Pimpinan
                </h3>
                @foreach ($tagihanDitolak as $t)
                    <div style="padding:8px 0; border-bottom:1px solid #FFE4E4">
                        <div style="display:flex; justify-content:space-between">
                            <a href="{{ route('tagihan.show', $t) }}"
                               style="color:#0E6E66; font-size:13px">
                                {{ $t->no_invoice }}
                            </a>
                            <span style="font-size:13px; color:#5B6470">
                                {{ $t->pelanggan->nama_pelanggan }}
                            </span>
                        </div>
                        <p style="font-size:12px; color:#B33A2E; margin:4px 0 0; font-style:italic">
                            {{ $t->approval_note }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

    @elseif (Auth::user()->isKeuangan())
        {{-- ==== LAYOUT BAGIAN KEUANGAN ==== --}}

        <div style="display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap">
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Total Belum Lunas
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
                    {{ $totalBelumLunas }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Jatuh Tempo Minggu Ini
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#B33A2E; font-weight:600">
                    {{ $jatuhTempoMingguIni }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Total Piutang
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
                    Rp {{ number_format((float) $totalPiutang, 0, ',', '.') }}
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

        <div style="display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap">
            <div style="flex:2; min-width:300px; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <h2 style="font-size:15px; font-weight:600; color:#1B2027; margin:0 0 12px 0">Top 5 Piutang Terbesar</h2>
                @forelse ($topPiutang as $i => $p)
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #EEF0EF">
                        <div style="display:flex; align-items:center; gap:10px; min-width:0">
                            <span style="width:20px; height:20px; border-radius:50%; background:#EEF0EF; color:#5B6470; font-size:11px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0">
                                {{ $i + 1 }}
                            </span>
                            <span style="font-size:13px; color:#1B2027; white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
                                {{ $p->nama_pelanggan }}
                            </span>
                        </div>
                        <span style="font-size:13px; font-family:'IBM Plex Mono',monospace; color:#1B2027; white-space:nowrap">
                            Rp {{ number_format((float) $p->total_piutang, 0, ',', '.') }}
                        </span>
                    </div>
                @empty
                    <p style="font-size:13px; color:#5B6470; margin:8px 0 0">Belum ada data piutang.</p>
                @endforelse
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

            <div style="flex:3; min-width:320px; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <h2 style="font-size:15px; font-weight:600; color:#1B2027; margin:0 0 12px 0">Kondisi Piutang Saat Ini</h2>
                @foreach ($bucketMeta as $key => $meta)
                    @php $data = $agingSummary[$key] ?? ['count' => 0, 'total' => 0]; @endphp
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px">
                        <span style="width:80px; font-size:12px; color:#5B6470">{{ $meta['label'] }}</span>
                        <div style="flex:1; height:6px; background:#F0F0F0; border-radius:3px; overflow:hidden">
                            <div style="height:100%; border-radius:3px; width:{{ ($data['count'] / $maxCount) * 100 }}%; background:{{ $meta['color'] }}"></div>
                        </div>
                        <span style="width:80px; font-size:12px; color:#5B6470; font-family:'IBM Plex Mono',monospace">{{ $data['count'] }} tagihan</span>
                        <span style="width:120px; font-size:12px; text-align:right; color:#1B2027; font-family:'IBM Plex Mono',monospace">Rp {{ number_format((float) $data['total'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

    @elseif (Auth::user()->isSales())
        {{-- ==== LAYOUT SALES ==== --}}

        <div style="display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap">
            <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
                <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Belum Ditagih
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#5B6470; font-weight:600">
                    {{ $ringkasanStatus['belum_ditagih'] }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #E5C99B; border-radius:8px; padding:16px; background:#FFFBF5">
                <div style="font-size:11px; color:#C8862A; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Sedang Ditagih
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#B8612A; font-weight:600">
                    {{ $ringkasanStatus['sedang_ditagih'] }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #D5DCE8; border-radius:8px; padding:16px; background:#F7F9FD">
                <div style="font-size:11px; color:#6B7CA3; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Janji Bayar
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#6B7CA3; font-weight:600">
                    {{ $ringkasanStatus['janji_bayar'] }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #BFD8C8; border-radius:8px; padding:16px; background:#F6FBF8">
                <div style="font-size:11px; color:#3E7C58; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Sudah Ditagih
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#3E7C58; font-weight:600">
                    {{ $ringkasanStatus['sudah_ditagih'] }}
                </div>
            </div>
        </div>

        <div class="bg-surface border border-line rounded overflow-hidden">
            <div class="px-4 py-3 border-b border-line">
                <h2 class="font-display text-lg font-semibold text-ink">Tagihan yang Perlu Ditagih ({{ $totalAssigned }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table style="width:100%; table-layout:fixed">
                    <thead>
                        <tr class="border-b border-line">
                            <th style="width:16%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">No. Invoice</th>
                            <th style="width:22%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Pelanggan</th>
                            <th style="width:20%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Lembaga</th>
                            <th style="width:12%; padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Total</th>
                            <th style="width:10%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Jatuh Tempo</th>
                            <th style="width:12%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Status Penagihan</th>
                            <th style="width:8%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tagihanAssigned as $t)
                            @php
                                $rail = match (true) {
                                    $t->status === 'lunas' => '#3E7C58',
                                    !$t->tanggal_jatuh_tempo->isPast() => '#6B7CA3',
                                    $t->days_overdue <= 30 => '#C8862A',
                                    $t->days_overdue <= 60 => '#B8612A',
                                    default => '#B33A2E',
                                };
                                $spColor = $t->status_penagihan_color;
                            @endphp
                            <tr class="border-b border-line" style="border-left:3px solid {{ $rail }}">
                                <td style="padding:12px 16px; font-size:13px; font-family:'IBM Plex Mono',monospace">
                                    <a href="{{ route('tagihan.show-sales', $t) }}" style="color:#0E6E66; text-decoration:none; font-weight:500">
                                        {{ $t->no_invoice }}
                                    </a>
                                </td>
                                <td style="padding:12px 16px; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" title="{{ $t->pelanggan->nama_pelanggan }}">
                                    {{ $t->pelanggan->nama_pelanggan }}
                                </td>
                                <td style="padding:12px 16px; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" title="{{ $t->pelanggan->nama_lembaga }}">
                                    {{ $t->pelanggan->nama_lembaga ?: '-' }}
                                </td>
                                <td style="padding:12px 16px; font-size:13px; font-family:'IBM Plex Mono',monospace; text-align:right; white-space:nowrap">
                                    Rp {{ number_format((float) $t->total_tagihan, 0, ',', '.') }}
                                </td>
                                <td style="padding:12px 16px; font-size:13px; font-family:'IBM Plex Mono',monospace">
                                    {{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}
                                </td>
                                <td style="padding:12px 16px; white-space:nowrap">
                                    <span style="font-size:11px; padding:2px 8px; border-radius:4px; white-space:nowrap; background:{{ $spColor }}20; color:{{ $spColor }}; border:1px solid {{ $spColor }}">
                                        {{ $t->status_penagihan_label }}
                                    </span>
                                </td>
                                <td style="padding:12px 16px; white-space:nowrap">
                                    <a href="{{ route('tagihan.show-sales', $t) }}"
                                       style="font-size:11px; padding:4px 10px; border-radius:4px; border:1px solid #0E6E66; color:#0E6E66; background:white; text-decoration:none; font-weight:500">
                                        Update Status
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding:32px; text-align:center; color:#5B6470; font-size:14px">
                                    Belum ada tagihan yang di-assign ke Anda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tagihanAssigned->hasPages())
                <div style="padding:12px 16px; border-top:1px solid #DCE2E0">
                    {{ $tagihanAssigned->links() }}
                </div>
            @endif
        </div>
    @endif
</x-app-layout>
