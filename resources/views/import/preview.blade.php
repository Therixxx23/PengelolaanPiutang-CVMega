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

                <form action="{{ route('import.store') }}" method="POST" class="mt-4 flex items-center gap-3"
                    x-data="{ open: false }">
                    @csrf
                    <button type="button" x-on:click="open = true"
                        class="px-4 py-2 rounded text-sm font-semibold bg-ink text-white hover:bg-ink-muted transition">
                        Konfirmasi &amp; Impor
                    </button>
                    <a href="{{ route('import.index') }}" class="text-sm text-ink-muted hover:text-ink transition">
                        Batal
                    </a>
                    <div x-show="open" x-cloak x-transition
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                        x-on:click.self="open = false">
                        <div class="bg-surface border border-line rounded-lg p-6 max-w-md w-full">
                            <h3 class="font-display text-base font-semibold text-ink">Konfirmasi Impor</h3>
                            <p class="text-sm text-ink-muted mt-1">
                                Impor akan membuat tagihan dan pelanggan baru dari file yang sudah dipratinjau.
                                Faktur yang sudah ada akan dilewati. Lanjutkan?
                            </p>
                            <div class="mt-4 flex items-center gap-3">
                                <button type="submit"
                                    class="px-4 py-2 rounded text-sm font-semibold bg-status-paid text-white hover:opacity-90 transition">
                                    Ya, Impor
                                </button>
                                <button type="button" x-on:click="open = false"
                                    class="text-sm text-ink-muted hover:text-ink transition">
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
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
                    <h2 class="font-display text-base font-semibold text-ink">Pratinjau Data (maks. 25 baris)</h2>
                </div>
                <div class="overflow-x-auto">
                    <table style="width:100%; table-layout:fixed">
                        <thead>
                            <tr class="border-b border-line">
                                <th style="width:18%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">No Invoice</th>
                                <th style="width:24%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Pelanggan</th>
                                <th style="width:18%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Tanggal</th>
                                <th style="width:15%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Total</th>
                                <th style="width:12%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php
                                    $noInvoice = $row['no_invoice'] ?? '—';
                                    $nama = $row['nama_pelanggan'] ?? ($row['nama_lembaga'] ?? '—');
                                    $tgl = $row['tanggal_tagihan'] ?? '—';
                                    $total = $row['total_tagihan'] ?? null;
                                    $totalFmt = $total !== null ? 'Rp '.number_format((float)$total, 0, ',', '.') : '—';
                                    $statusFile = mb_strtolower((string)($row['status_tagihan'] ?? ''));
                                    $lunas = in_array($statusFile, ['lunas','paid','ya'], true);
                                @endphp
                                <tr class="border-b border-line">
                                    <td style="padding:12px 16px; font-size:13px; font-family:'IBM Plex Mono',monospace">{{ $noInvoice }}</td>
                                    <td style="padding:12px 16px; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="{{ $nama }}">{{ $nama }}</td>
                                    <td style="padding:12px 16px; font-size:13px">{{ $tgl }}</td>
                                    <td style="padding:12px 16px; font-size:13px">{{ $totalFmt }}</td>
                                    <td style="padding:12px 16px; font-size:12px">
                                        <span style="font-size:11px; padding:2px 8px; border-radius:4px; background:{{ $lunas ? '#3E7C58' : '#B08A2A' }}20; color:{{ $lunas ? '#3E7C58' : '#B08A2A' }}; border:1px solid {{ $lunas ? '#3E7C58' : '#B08A2A' }}">
                                            {{ $lunas ? 'Lunas' : 'Belum Lunas' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="padding:32px; text-align:center; color:#5B6470; font-size:14px">
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
