<x-app-layout>
    <x-slot name="header">Pratinjau Import SIPLAH</x-slot>

    <div class="space-y-4">
        @unless($success ?? false)
            <div class="bg-status-critical/10 border border-status-critical rounded px-4 py-3 text-sm text-status-critical font-medium">
                {{ $message }}
                <a href="{{ route('import.index') }}" class="underline">Kembali ke unggah</a>.
            </div>
        @else
            <div class="bg-surface border border-line rounded p-5">
                <h2 class="font-display text-lg font-semibold text-ink">{{ $message }}</h2>
                <p class="text-sm text-ink-muted mt-1">
                    {{ $summary['total_baris'] }} baris data terdeteksi ·
                    {{ $summary['kolom_terdeteksi'] }} kolom dikenali.
                </p>

                <div class="mt-4 flex items-center gap-3">
                    <form action="{{ route('import.cancel') }}" method="POST" style="display:inline">
                        @csrf
                        <button type="submit"
                            style="padding:10px 24px; border:1px solid #DCE2E0; border-radius:6px; background:white; color:#5B6470; font-size:14px; cursor:pointer">
                            Batal
                        </button>
                    </form>

                    <form action="{{ route('import.store') }}" method="POST" style="display:inline; margin-left:8px">
                        @csrf
                        <button type="submit"
                            style="padding:10px 24px; background:#0E6E66; color:white; border:none; border-radius:6px; font-size:14px; cursor:pointer"
                            onclick="return confirm('Yakin ingin mengimport data ini ke sistem? Proses tidak bisa dibatalkan setelah dikonfirmasi.')">
                            &#10003; Konfirmasi &amp; Import
                        </button>
                    </form>
                </div>
            </div>

            @php
                $ring = $ringkasan ?? [];
                $totalFaktur = $ring['totalFaktur'] ?? 0;
                $fakturBaru = $ring['fakturBaru'] ?? 0;
                $fakturSkip = $ring['fakturSkip'] ?? 0;
                $pelangganBaru = $ring['pelangganBaru'] ?? 0;
            @endphp
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="bg-surface border border-line rounded p-4">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-wide">Total Faktur</p>
                    <p class="font-display text-2xl font-semibold text-ink mt-1">{{ number_format($totalFaktur, 0, ',', '.') }}</p>
                </div>
                <div class="bg-surface border border-line rounded p-4">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-wide">Faktur Baru</p>
                    <p class="font-display text-2xl font-semibold text-status-paid mt-1">{{ number_format($fakturBaru, 0, ',', '.') }}</p>
                </div>
                <div class="bg-surface border border-line rounded p-4">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-wide">Di-Skip (Sudah Ada)</p>
                    <p class="font-display text-2xl font-semibold text-status-watch30 mt-1">{{ number_format($fakturSkip, 0, ',', '.') }}</p>
                </div>
                <div class="bg-surface border border-line rounded p-4">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-wide">Pelanggan Baru</p>
                    <p class="font-display text-2xl font-semibold text-status-critical mt-1">{{ number_format($pelangganBaru, 0, ',', '.') }}</p>
                </div>
            </div>

            @if(count($header) > 0)
                <div class="bg-surface border border-line rounded overflow-hidden">
                    <div class="px-4 py-3 border-b border-line">
                        <h2 class="font-display text-base font-semibold text-ink">Kolom Terdeteksi</h2>
                    </div>
                    <div class="px-4 py-3 flex flex-wrap gap-2">
                        @foreach($header as $col)
                            <span class="font-mono text-xs px-2 py-1 rounded bg-line text-ink">
                                {{ $col }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-surface border border-line rounded overflow-hidden">
                <div class="px-4 py-3 border-b border-line">
                    <h2 class="font-display text-base font-semibold text-ink">Pratinjau Data (maks. 25 faktur)</h2>
                </div>
                <div class="overflow-x-auto">
                    <table style="width:100%; table-layout:fixed">
                        <thead>
                            <tr class="border-b border-line">
                                <th style="padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">No Invoice</th>
                                <th style="padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Tgl</th>
                                <th style="padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Sales</th>
                                <th style="padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Pelanggan</th>
                                <th style="padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Lembaga</th>
                                <th style="padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Dana</th>
                                <th style="padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Total</th>
                                <th style="padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Items</th>
                                <th style="padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php
                                    $noInvoice = $row['no_invoice'] ?? '—';
                                    $tgl = $row['tanggal'] ?? '—';
                                    $sales = $row['nama_sales'] ?? '—';
                                    $pelanggan = $row['nama_pelanggan'] ?? '—';
                                    $lembaga = $row['nama_lembaga'] ?? '—';
                                    $dana = $row['sumber_dana'] ?? '—';
                                    $total = $row['total_tagihan'] ?? null;
                                    $totalFmt = $total !== null ? 'Rp '.number_format((float)$total, 0, ',', '.') : '—';
                                    $items = $row['jumlah_item'] ?? 0;
                                    $sudahAda = (bool)($row['sudah_ada'] ?? false);
                                    $pelangganBaruRow = (bool)($row['pelanggan_baru'] ?? false);
                                @endphp
                                <tr class="border-b border-line">
                                    <td style="padding:12px 16px; font-size:13px; font-family:'IBM Plex Mono',monospace">{{ $noInvoice }}</td>
                                    <td style="padding:12px 16px; font-size:13px">{{ $tgl }}</td>
                                    <td style="padding:12px 16px; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="{{ $sales }}">{{ $sales }}</td>
                                    <td style="padding:12px 16px; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="{{ $pelanggan }}">{{ $pelanggan }}</td>
                                    <td style="padding:12px 16px; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="{{ $lembaga }}">{{ $lembaga }}</td>
                                    <td style="padding:12px 16px; font-size:13px">{{ $dana }}</td>
                                    <td style="padding:12px 16px; font-size:13px; font-family:'IBM Plex Mono',monospace">{{ $totalFmt }}</td>
                                    <td style="padding:12px 16px; font-size:13px; text-align:center">{{ $items }}</td>
                                    <td style="padding:12px 16px; font-size:12px">
                                        @if($sudahAda)
                                            <span style="font-size:11px; padding:2px 8px; border-radius:4px; background:#B08A2A20; color:#B08A2A; border:1px solid #B08A2A">Sudah Ada</span>
                                        @else
                                            @if($pelangganBaruRow)
                                                <span style="font-size:11px; padding:2px 8px; border-radius:4px; background:#0E6E6620; color:#0E6E66; border:1px solid #0E6E66">Baru</span>
                                            @else
                                                <span style="font-size:11px; padding:2px 8px; border-radius:4px; background:#5B647020; color:#5B6470; border:1px solid #5B6470">Akan Import</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" style="padding:32px; text-align:center; color:#5B6470; font-size:14px">
                                        Tidak ada baris data untuk dipratinjau.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endunless
    </div>
</x-app-layout>
