<x-app-layout>
    <x-slot name="header">Riwayat Pembayaran</x-slot>

    <div class="bg-surface border border-line rounded overflow-hidden mb-6">
        <form method="GET" class="p-4 flex flex-wrap items-end gap-4">
            <div>
                <label for="id_pelanggan" class="block text-xs font-medium text-ink-muted mb-1">Pelanggan</label>
                <select id="id_pelanggan" name="id_pelanggan" class="input-field text-sm">
                    <option value="">Semua Pelanggan</option>
                    @foreach ($pelanggan as $p)
                        <option value="{{ $p->id_pelanggan }}" {{ request('id_pelanggan') == $p->id_pelanggan ? 'selected' : '' }}>
                            {{ $p->nama_pelanggan }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dari" class="block text-xs font-medium text-ink-muted mb-1">Dari Tanggal</label>
                <input type="date" id="dari" name="dari" value="{{ request('dari') }}" class="input-field text-sm">
            </div>
            <div>
                <label for="sampai" class="block text-xs font-medium text-ink-muted mb-1">Sampai Tanggal</label>
                <input type="date" id="sampai" name="sampai" value="{{ request('sampai') }}" class="input-field text-sm">
            </div>
            <button type="submit" class="btn-primary">Filter</button>
            @if(request()->anyFilled(['id_pelanggan', 'dari', 'sampai']))
                <a href="{{ route('riwayat-pembayaran') }}" class="btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    <div class="bg-surface border border-line rounded overflow-hidden">
        <div class="px-4 py-3 border-b border-line">
            <p class="text-sm text-ink-muted">{{ $pembayaran->total() }} pembayaran ditemukan</p>
        </div>

        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-line">
                        <th class="table-header">Tanggal</th>
                        <th class="table-header">No. Invoice</th>
                        <th class="table-header">Pelanggan</th>
                        <th class="table-header">Metode</th>
                        <th class="table-header text-right">Jumlah</th>
                        <th class="table-header text-right">Sisa Tagihan</th>
                        <th class="table-header">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pembayaran as $p)
                        @php
                            $totalBayar = $p->tagihan->pembayaran->sum('jumlah_bayar');
                            $totalTagihan = $p->tagihan->total_tagihan;
                            $sisa = $totalTagihan - $totalBayar;
                        @endphp
                        <tr class="border-b border-line hover:bg-paper transition">
                            <td class="table-cell font-mono">{{ $p->tanggal_bayar->format('d/m/Y') }}</td>
                            <td class="table-cell">
                                @if(Auth::user()->isAdministrasi())
                                    <a href="{{ route('tagihan.show', $p->tagihan) }}" class="text-action hover:underline font-mono">
                                        {{ $p->tagihan->no_invoice }}
                                    </a>
                                @else
                                    <span class="font-mono text-ink">{{ $p->tagihan->no_invoice }}</span>
                                @endif
                            </td>
                            <td class="table-cell">
                                @if(Auth::user()->isAdministrasi())
                                    <a href="{{ route('pelanggan.show', $p->tagihan->pelanggan) }}" class="text-action hover:underline">
                                        {{ $p->tagihan->pelanggan->nama_pelanggan }}
                                    </a>
                                @else
                                    <span class="text-ink">{{ $p->tagihan->pelanggan->nama_pelanggan }}</span>
                                @endif
                            </td>
                            <td class="table-cell">{{ ucfirst($p->metode_bayar) }}</td>
                            <td class="table-cell text-right font-mono">Rp {{ number_format($p->jumlah_bayar, 2) }}</td>
                            <td class="table-cell text-right font-mono {{ $sisa > 0 ? 'text-status-watch30' : 'text-status-paid' }}">
                                Rp {{ number_format(max(0, $sisa), 2) }}
                                @if ($sisa > 0)
                                    <span class="text-xs text-ink-muted">(sisa)</span>
                                @else
                                    <span class="text-xs text-status-paid">(lunas)</span>
                                @endif
                            </td>
                            <td class="table-cell text-ink-muted">{{ $p->keterangan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-ink-muted">
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
                    $totalTagihan = $p->tagihan->total_tagihan;
                    $sisa = $totalTagihan - $totalBayar;
                @endphp
                <div class="p-4 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-mono">{{ $p->tanggal_bayar->format('d/m/Y') }}</span>
                        <span class="font-mono">Rp {{ number_format($p->jumlah_bayar, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        @if(Auth::user()->isAdministrasi())
                            <a href="{{ route('tagihan.show', $p->tagihan) }}" class="text-action hover:underline font-mono">
                                {{ $p->tagihan->no_invoice }}
                            </a>
                        @else
                            <span class="font-mono text-ink">{{ $p->tagihan->no_invoice }}</span>
                        @endif
                        <span class="text-xs {{ $sisa > 0 ? 'text-status-watch30' : 'text-status-paid' }}">
                            Sisa: Rp {{ number_format(max(0, $sisa), 2) }}
                            ({{ $sisa > 0 ? 'sisa' : 'lunas' }})
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-ink-muted">
                        @if(Auth::user()->isAdministrasi())
                            <a href="{{ route('pelanggan.show', $p->tagihan->pelanggan) }}" class="text-action hover:underline">
                                {{ $p->tagihan->pelanggan->nama_pelanggan }}
                            </a>
                        @else
                            <span class="text-ink">{{ $p->tagihan->pelanggan->nama_pelanggan }}</span>
                        @endif
                        <span>{{ ucfirst($p->metode_bayar) }}{{ $p->keterangan ? ' — '.$p->keterangan : '' }}</span>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-ink-muted text-sm">
                    Belum ada pembayaran yang tercatat. Sesuaikan filter atau buat pembayaran baru dari halaman tagihan.
                </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-line hidden sm:block">
            {{ $pembayaran->withQueryString()->links() }}
        </div>
    </div>
</x-app-layout>
