<x-app-layout>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:8px">
        <div>
            <h1 class="font-display text-2xl font-semibold text-ink">Laporan Data Import SIPLAH</h1>
            <p style="font-size:13px; color:#5B6470; margin-top:4px">CV. Mega Setia Abadi</p>
        </div>
        <a href="{{ route('laporan.import-siplah.export', request()->only('periode', 'wilayah', 'sumber_dana', 'sales')) }}"
           style="background:#0E6E66; color:white; padding:8px 16px; border-radius:6px; font-size:14px; text-decoration:none; font-family:'IBM Plex Sans',sans-serif; font-weight:500">
            Export Excel
        </a>
    </div>

    <form method="GET" action="{{ route('laporan.import-siplah') }}">
        <div style="display:flex; gap:10px; align-items:center; margin-bottom:16px; flex-wrap:wrap">
            <select name="periode" style="width:130px; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
                <option value="semua" {{ $periode === 'semua' ? 'selected' : '' }}>Semua Periode</option>
                @foreach($daftarPeriode as $p)
                    <option value="{{ $p }}" {{ $periode === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>

            <select name="wilayah" style="width:160px; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
                <option value="semua" {{ $wilayah === 'semua' ? 'selected' : '' }}>Semua Wilayah</option>
                @foreach($daftarWilayah as $w)
                    <option value="{{ $w }}" {{ $wilayah === $w ? 'selected' : '' }}>{{ $w }}</option>
                @endforeach
            </select>

            <select name="sumber_dana" style="width:140px; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
                <option value="semua" {{ $sumber === 'semua' ? 'selected' : '' }}>Semua Dana</option>
                @foreach($daftarSumber as $s)
                    <option value="{{ $s }}" {{ $sumber === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>

            <select name="sales" style="width:180px; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
                <option value="semua" {{ $sales === 'semua' ? 'selected' : '' }}>Semua Sales</option>
                @foreach($daftarSales as $s)
                    <option value="{{ $s }}" {{ $sales === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>

            <button type="submit" style="padding:8px 20px; background:#0E6E66; color:white; border:none; border-radius:6px; font-size:14px; cursor:pointer; font-family:'IBM Plex Sans',sans-serif; font-weight:500">Filter</button>

            @if($periode !== 'semua' || $wilayah !== 'semua' || $sumber !== 'semua' || $sales !== 'semua')
                <a href="{{ route('laporan.import-siplah') }}" style="padding:8px 16px; border:1px solid #DCE2E0; border-radius:6px; font-size:14px; color:#5B6470; font-family:Inter,sans-serif; text-decoration:none">Reset</a>
            @endif
        </div>
    </form>

    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:16px">
        <div style="border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">Total Faktur</div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">{{ number_format($summary['total_faktur'], 0, ',', '.') }}</div>
        </div>
        <div style="border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">Total Nilai</div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#0E6E66; font-weight:600">Rp {{ number_format($summary['total_nilai'], 0, ',', '.') }}</div>
        </div>
        <div style="border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">Total Item</div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">{{ number_format($summary['total_item'], 0, ',', '.') }}</div>
        </div>
        <div style="border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">Total Qty</div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#1B2027; font-weight:600">{{ number_format($summary['total_qty'], 0, ',', '.') }} buku</div>
        </div>
        <div style="border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">Sudah Lunas</div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#3E7C58; font-weight:600">{{ number_format($summary['sudah_lunas'], 0, ',', '.') }} faktur</div>
        </div>
        <div style="border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">Belum Lunas</div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#B33A2E; font-weight:600">{{ number_format($summary['belum_lunas'], 0, ',', '.') }} faktur</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px">
        <div style="border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:12px">Per Sumber Dana</div>
            @forelse ($perSumberDana as $dana => $brk)
                @php
                    $color = $loop->first ? '#B8612A' : '#6B7CA3';
                @endphp
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border:1px solid #DCE2E0; border-radius:6px; margin-bottom:8px">
                    <span style="font-weight:600; color:#1B2027; font-size:14px">{{ $dana ?: '-' }}</span>
                    <span style="font-size:12px; color:#5B6470">{{ $brk['jumlah_faktur'] }} faktur</span>
                    <span style="font-family:'IBM Plex Mono',monospace; font-size:14px; color:{{ $color }}; font-weight:600">Rp {{ number_format($brk['total_nilai'], 0, ',', '.') }}</span>
                </div>
            @empty
                <p style="color:#5B6470; font-size:14px">Tidak ada data.</p>
            @endforelse
        </div>

        <div style="border:1px solid #DCE2E0; border-radius:8px; padding:16px">
            <div style="font-size:11px; color:#5B6470; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:12px">Performa Sales</div>
            @forelse ($perSales->take(10) as $nama => $brk)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #EEF0EF">
                    <span style="font-weight:500; color:#1B2027; font-size:14px">{{ $loop->iteration }}. {{ $nama ?: '-' }}</span>
                    <span style="font-size:12px; color:#5B6470">{{ $brk['jumlah_faktur'] }} faktur</span>
                    <span style="font-family:'IBM Plex Mono',monospace; font-size:13px; color:#0E6E66; font-weight:600">Rp {{ number_format($brk['total_nilai'], 0, ',', '.') }}</span>
                </div>
            @empty
                <p style="color:#5B6470; font-size:14px">Tidak ada data.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-surface border border-line rounded overflow-hidden">
        <div class="hidden sm:block overflow-x-auto">
            <table style="width:100%; table-layout:fixed">
                <thead>
                    <tr class="border-b border-line">
                        <th style="width:16%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">No. Faktur</th>
                        <th style="width:10%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">No. SJ</th>
                        <th style="width:10%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Tanggal</th>
                        <th style="width:16%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Pelanggan</th>
                        <th style="width:9%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Kabupaten</th>
                        <th style="width:11%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Sales</th>
                        <th style="width:8%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Dana</th>
                        <th style="width:6%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Item</th>
                        <th style="width:9%; padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Total</th>
                        <th style="width:8%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Status</th>
                    </tr>
                </thead>
                    @forelse ($tagihan as $t)
                        <tbody x-data="{ open: false }">
                            <tr x-on:click="open = !open" style="cursor:pointer; border-left:3px solid {{ $t->status === 'lunas' ? '#3E7C58' : '#B33A2E' }}" class="border-b border-line hover:bg-paper transition">
                                <td style="padding:12px 16px; font-family:'IBM Plex Mono',monospace; font-size:13px; white-space:nowrap">
                                    <span x-text="open ? '\u25BC' : '\u25B6'" style="font-size:10px; color:#5B6470; margin-right:6px"></span>
                                    {{ $t->no_invoice }}
                                </td>
                                <td style="padding:12px 16px; font-size:13px; font-family:'IBM Plex Mono',monospace">{{ $t->no_sj ?: '-' }}</td>
                                <td style="padding:12px 16px; font-size:13px">{{ $t->tanggal_tagihan?->format('d/m/Y') }}</td>
                                <td style="padding:12px 16px; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:0" title="{{ $t->pelanggan?->nama_pelanggan }}">
                                    {{ $t->pelanggan?->nama_lembaga ?: $t->pelanggan?->nama_pelanggan }}
                                </td>
                                <td style="padding:12px 16px; font-size:13px; white-space:nowrap">{{ $t->pelanggan?->kabupaten ?: '-' }}</td>
                                <td style="padding:12px 16px; font-size:13px; white-space:nowrap">{{ $t->nama_sales ?: '-' }}</td>
                                <td style="padding:12px 16px; font-size:13px; white-space:nowrap">{{ $t->sumber_dana ?: '-' }}</td>
                                <td style="padding:12px 16px; font-size:13px">{{ $t->items->count() }}</td>
                                <td style="padding:12px 16px; text-align:right; font-family:'IBM Plex Mono',monospace; font-size:13px; white-space:nowrap">Rp {{ number_format($t->total_tagihan, 0, ',', '.') }}</td>
                                <td style="padding:12px 16px; white-space:nowrap">
                                    <span style="font-size:11px; padding:2px 8px; border-radius:4px; background:{{ $t->status === 'lunas' ? '#3E7C58' : '#B33A2E' }}20; color:{{ $t->status === 'lunas' ? '#3E7C58' : '#B33A2E' }}">
                                        {{ $t->status === 'lunas' ? 'Lunas' : 'Belum Lunas' }}
                                    </span>
                                </td>
                            </tr>
                            <tr x-show="open" x-transition class="border-b border-line">
                                <td colspan="10" style="padding:0; background:#F9FAFB">
                                    <table style="width:100%; font-size:12px">
                                        <thead style="background:#EEF2F7">
                                            <tr>
                                                <th style="padding:6px 12px; text-align:left; font-size:11px; font-weight:500; color:#5B6470">Kode Barang</th>
                                                <th style="text-align:left; font-size:11px; font-weight:500; color:#5B6470">Nama Barang</th>
                                                <th style="text-align:left; font-size:11px; font-weight:500; color:#5B6470">Kelas</th>
                                                <th style="text-align:left; font-size:11px; font-weight:500; color:#5B6470">Supplier</th>
                                                <th style="text-align:right; font-size:11px; font-weight:500; color:#5B6470">Qty</th>
                                                <th style="text-align:right; font-size:11px; font-weight:500; color:#5B6470">Harga Jual</th>
                                                <th style="text-align:right; font-size:11px; font-weight:500; color:#5B6470">Diskon</th>
                                                <th style="text-align:right; font-size:11px; font-weight:500; color:#5B6470">Netto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($t->items as $item)
                                                <tr style="border-bottom:1px solid #EEF2F7">
                                                    <td style="padding:6px 12px; font-family:'IBM Plex Mono',monospace; font-size:11px">{{ $item->kode_barang ?: '-' }}</td>
                                                    <td>{{ $item->nama_barang }}</td>
                                                    <td>{{ $item->kelas ?: '-' }}</td>
                                                    <td>{{ $item->nama_supplier ?: '-' }}</td>
                                                    <td style="text-align:right; font-family:'IBM Plex Mono',monospace">{{ $item->qty_netto }}</td>
                                                    <td style="text-align:right; font-family:'IBM Plex Mono',monospace">Rp {{ number_format((float) $item->harga_jual, 0, ',', '.') }}</td>
                                                    <td style="text-align:right; font-family:'IBM Plex Mono',monospace">{{ $item->persen_diskon ?: 0 }}%</td>
                                                    <td style="text-align:right; font-family:'IBM Plex Mono',monospace">Rp {{ number_format((float) $item->netto_penj, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                            <tr style="background:#F0FAF5; font-weight:600">
                                                <td colspan="6" style="padding:6px 12px; text-align:right">Total {{ $t->items->count() }} item, {{ $t->items->sum('qty_netto') }} buku:</td>
                                                <td></td>
                                                <td style="text-align:right; font-family:'IBM Plex Mono',monospace">Rp {{ number_format($t->items->sum('netto_penj'), 0, ',', '.') }}</td>
                                            </tr>
                                        </tbody>
                                     </table>
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="10" style="padding:32px; text-align:center; color:#5B6470; font-size:14px">Tidak ada data import SIPLAH yang cocok dengan filter ini.</td>
                            </tr>
                        </tbody>
                    @endforelse
            </table>
        </div>

        <div class="px-4 py-3 border-t border-line hidden sm:block">
            {{ $tagihan->links() }}
        </div>
    </div>
</x-app-layout>
