<x-app-layout>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
        <h1 class="font-display text-2xl font-semibold text-ink">Riwayat Pembayaran</h1>
    </div>

    <form method="GET" action="{{ route('riwayat-pembayaran') }}">
        <div style="display:flex; gap:8px; align-items:flex-end; margin-bottom:16px; flex-wrap:wrap">
            <div style="display:flex; flex-direction:column; gap:4px">
                <label style="font-size:11px; color:#5B6470; font-weight:500">PELANGGAN</label>
                <select name="id_pelanggan"
                        style="width:200px; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
                    <option value="">Semua Pelanggan</option>
                    @foreach($pelanggan as $pl)
                        <option value="{{ $pl->id_pelanggan }}" {{ request('id_pelanggan') == $pl->id_pelanggan ? 'selected' : '' }}>
                            {{ $pl->nama_pelanggan }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex; flex-direction:column; gap:4px">
                <label style="font-size:11px; color:#5B6470; font-weight:500">DARI TANGGAL</label>
                <input type="date" name="dari"
                       value="{{ request('dari') }}"
                       style="padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
            </div>

            <div style="display:flex; flex-direction:column; gap:4px">
                <label style="font-size:11px; color:#5B6470; font-weight:500">SAMPAI TANGGAL</label>
                <input type="date" name="sampai"
                       value="{{ request('sampai') }}"
                       style="padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
            </div>

            <button type="submit"
                    style="padding:8px 20px; background:#0E6E66; color:white; border:none; border-radius:6px; font-size:14px; cursor:pointer; font-family:'IBM Plex Sans',sans-serif; font-weight:500">
                Filter
            </button>

            @if(request()->anyFilled(['id_pelanggan', 'dari', 'sampai']))
                <a href="{{ route('riwayat-pembayaran') }}"
                   style="padding:8px 16px; border:1px solid #DCE2E0; border-radius:6px; font-size:14px; color:#5B6470; font-family:Inter,sans-serif; text-decoration:none">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <div style="display:flex; gap:16px; margin-bottom:16px">
        <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                Total Pembayaran
            </div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
                Rp {{ number_format($summary['total'], 0, ',', '.') }}
            </div>
        </div>
        <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                Rata-rata / Transaksi
            </div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
                Rp {{ number_format($summary['rata_rata'], 0, ',', '.') }}
            </div>
        </div>
        <div style="flex:1; border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                Jumlah Transaksi
            </div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">
                {{ $summary['jumlah'] }}
            </div>
        </div>
    </div>

    <div class="bg-surface border border-line rounded overflow-hidden">
        <div class="hidden sm:block overflow-x-auto">
            <table style="width:100%; table-layout:fixed">
                <thead>
                    <tr class="border-b border-line">
                        <th style="width:10%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Tanggal</th>
                        <th style="width:18%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">No. Invoice</th>
                        <th style="width:16%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Pelanggan</th>
                        <th style="width:9%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Metode</th>
                        <th style="width:15%; padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Jumlah</th>
                        <th style="width:15%; padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Sisa Tagihan</th>
                        <th style="width:17%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pembayaran as $p)
                        @php
                            $totalBayar = $p->tagihan->pembayaran->sum('jumlah_bayar');
                            $sisa = max(0, $p->tagihan->total_tagihan - $totalBayar);
                            $sisaLunas = $sisa <= 0;
                            $badgeColor = $sisaLunas ? '#3E7C58' : '#B33A2E';
                            $badgeLabel = $sisaLunas ? 'Lunas' : 'Sisa';
                        @endphp
                        <tr class="border-b border-line hover:bg-paper transition">
                            <td style="padding:12px 16px; font-size:14px; font-family:'IBM Plex Mono',monospace">
                                {{ $p->tanggal_bayar->format('d/m/Y') }}
                            </td>
                            <td style="padding:12px 16px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0">
                                <a href="{{ route('tagihan.show', $p->tagihan) }}"
                                   style="color:#0E6E66; text-decoration:none; font-family:'IBM Plex Mono',monospace; font-size:13px">
                                    {{ $p->tagihan->no_invoice }}
                                </a>
                            </td>
                            <td style="padding:12px 16px; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0"
                                title="{{ $p->tagihan->pelanggan->nama_pelanggan }}">
                                {{ $p->tagihan->pelanggan->nama_pelanggan }}
                            </td>
                            <td style="padding:12px 16px; font-size:14px">
                                {{ ucfirst($p->metode_bayar) }}
                            </td>
                            <td style="padding:12px 16px; text-align:right; white-space:nowrap; font-family:'IBM Plex Mono',monospace; font-size:14px">
                                Rp {{ number_format($p->jumlah_bayar, 2, ',', '.') }}
                            </td>
                            <td style="padding:12px 16px; text-align:right; white-space:nowrap">
                                <div style="font-family:'IBM Plex Mono',monospace; font-size:13px; color:{{ $badgeColor }}">
                                    Rp {{ number_format($sisa, 2, ',', '.') }}
                                </div>
                                <span style="font-size:10px; padding:1px 6px; border-radius:3px; background:{{ $badgeColor }}20; color:{{ $badgeColor }}; border:1px solid {{ $badgeColor }}">
                                    {{ $badgeLabel }}
                                </span>
                            </td>
                            <td style="padding:12px 16px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0; font-size:13px; color:#5B6470"
                                title="{{ $p->keterangan ?? '-' }}">
                                {{ $p->keterangan ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:32px; text-align:center; color:#5B6470; font-size:14px">
                                Belum ada pembayaran yang tercatat. Sesuaikan filter atau buat pembayaran baru dari halaman tagihan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sm:hidden divide-y divide-line">
            @forelse ($pembayaran as $p)
                @php
                    $totalBayar = $p->tagihan->pembayaran->sum('jumlah_bayar');
                    $sisa = max(0, $p->tagihan->total_tagihan - $totalBayar);
                    $sisaLunas = $sisa <= 0;
                    $badgeColor = $sisaLunas ? '#3E7C58' : '#B33A2E';
                    $badgeLabel = $sisaLunas ? 'Lunas' : 'Sisa';
                @endphp
                <div class="p-4 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span style="font-family:'IBM Plex Mono',monospace">{{ $p->tanggal_bayar->format('d/m/Y') }}</span>
                        <span style="font-family:'IBM Plex Mono',monospace; font-weight:500">Rp {{ number_format($p->jumlah_bayar, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <a href="{{ route('tagihan.show', $p->tagihan) }}"
                           style="color:#0E6E66; text-decoration:none; font-family:'IBM Plex Mono',monospace">
                            {{ $p->tagihan->no_invoice }}
                        </a>
                        <span style="font-size:11px; padding:1px 6px; border-radius:3px; background:{{ $badgeColor }}20; color:{{ $badgeColor }}; border:1px solid {{ $badgeColor }}">
                            {{ $badgeLabel }} · Rp {{ number_format($sisa, 2, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs" style="color:#5B6470">
                        <span>{{ $p->tagihan->pelanggan->nama_pelanggan }}</span>
                        <span>{{ ucfirst($p->metode_bayar) }}{{ $p->keterangan ? ' — '.$p->keterangan : '' }}</span>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center" style="color:#5B6470; font-size:14px">
                    Belum ada pembayaran yang tercatat. Sesuaikan filter atau buat pembayaran baru dari halaman tagihan.
                </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-line hidden sm:block">
            {{ $pembayaran->links() }}
        </div>
    </div>
</x-app-layout>
