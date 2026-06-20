<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>{{ $tagihan->no_invoice }}</span>
            @php
                if ($tagihan->status === 'lunas') {
                    $badgeClass = 'badge-paid';
                    $badgeText = 'Lunas';
                } elseif ($tagihan->is_overdue) {
                    $bucket = $tagihan->aging_bucket;
                    $badgeClass = $bucket === '0-30' ? 'badge-watch30' : ($bucket === '31-60' ? 'badge-watch60' : 'badge-critical');
                    $badgeText = $bucket === '0-30' ? 'Jatuh Tempo (1-30 hr)' : ($bucket === '31-60' ? 'Jatuh Tempo (31-60 hr)' : 'Jatuh Tempo (>60 hr)');
                } else {
                    $badgeClass = 'badge-lancar';
                    $badgeText = 'Belum Lunas';
                }
            @endphp
            <span class="{{ $badgeClass }} text-sm">{{ $badgeText }}</span>
        </div>
    </x-slot>

    @php
        $railClass = $tagihan->status === 'lunas' ? 'aging-rail-paid' : ($tagihan->is_overdue ? ($tagihan->aging_bucket === '0-30' ? 'aging-rail-watch30' : ($tagihan->aging_bucket === '31-60' ? 'aging-rail-watch60' : 'aging-rail-critical')) : 'aging-rail-lancar');
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-surface border border-line border-l-[3px] rounded overflow-hidden {{ $railClass }}">
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Pelanggan</p>
                        <p class="text-sm text-ink mt-1">
                            <a href="{{ route('pelanggan.show', $tagihan->pelanggan) }}" class="text-action hover:underline">
                                {{ $tagihan->pelanggan->nama_pelanggan }}
                            </a>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Wilayah</p>
                        <p class="text-sm text-ink mt-1">{{ $tagihan->pelanggan->wilayah ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Tanggal Tagihan</p>
                        <p class="text-sm text-ink font-mono mt-1">{{ $tagihan->tanggal_tagihan->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Jatuh Tempo</p>
                        <p class="text-sm text-ink font-mono mt-1">{{ $tagihan->tanggal_jatuh_tempo->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Total Tagihan</p>
                        <p class="text-lg text-ink font-mono mt-1">Rp {{ number_format($tagihan->total_tagihan, 2) }}</p>
                    </div>
                    <div>
                        @php
                            $totalDibayar = $tagihan->pembayaran->sum('jumlah_bayar');
                            $sisa = $tagihan->total_tagihan - $totalDibayar;
                        @endphp
                        <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Total Dibayar</p>
                        <p class="text-sm text-ink font-mono mt-1">Rp {{ number_format($totalDibayar, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Sisa Tagihan</p>
                        <p class="text-sm {{ $sisa > 0 ? 'text-status-watch30' : 'text-status-paid' }} font-mono mt-1">Rp {{ number_format(max(0, $sisa), 2) }}</p>
                    </div>

                    <div class="pt-3 border-t border-line flex gap-2">
                        @can('update', $tagihan)
                            <a href="{{ route('tagihan.edit', $tagihan) }}" class="btn-secondary">Edit</a>
                        @endcan
                        @can('delete', $tagihan)
                            <form action="{{ route('tagihan.destroy', $tagihan) }}" method="POST" onsubmit="return confirm('Hapus tagihan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-destructive">Hapus</button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            {{-- Catat Pembayaran --}}
            @can('create', App\Models\Pembayaran::class)
                @if ($sisa > 0)
                    <div class="bg-surface border border-line rounded p-6">
                        <h2 class="font-display text-lg font-semibold text-ink mb-4">Catat Pembayaran</h2>
                        <form action="{{ route('tagihan.bayar', $tagihan) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="tanggal_bayar" class="block text-sm font-medium text-ink mb-1">Tanggal Bayar</label>
                                    <input type="date" id="tanggal_bayar" name="tanggal_bayar" value="{{ old('tanggal_bayar', now()->format('Y-m-d')) }}" class="input-field" required>
                                    @error('tanggal_bayar') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="metode_bayar" class="block text-sm font-medium text-ink mb-1">Metode</label>
                                    <select id="metode_bayar" name="metode_bayar" class="input-field" required>
                                        <option value="tunai" {{ old('metode_bayar') === 'tunai' ? 'selected' : '' }}>Tunai</option>
                                        <option value="transfer" {{ old('metode_bayar') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                                        <option value="giro" {{ old('metode_bayar') === 'giro' ? 'selected' : '' }}>Giro</option>
                                    </select>
                                    @error('metode_bayar') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label for="jumlah_bayar" class="block text-sm font-medium text-ink mb-1">
                                    Jumlah Bayar (Rp) — maksimal Rp {{ number_format($sisa, 2) }}
                                </label>
                                <input type="text" id="jumlah_bayar" name="jumlah_bayar" value="{{ old('jumlah_bayar') }}" class="input-field font-mono text-right" inputmode="numeric" placeholder="0" required>
                                @error('jumlah_bayar') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="keterangan" class="block text-sm font-medium text-ink mb-1">Keterangan (opsional)</label>
                                <input type="text" id="keterangan" name="keterangan" value="{{ old('keterangan') }}" class="input-field">
                                @error('keterangan') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" class="btn-primary">Catat Pembayaran</button>
                        </form>
                    </div>
                @else
                    <div class="bg-surface border border-line border-l-[3px] border-status-paid rounded p-6">
                        <p class="text-sm text-status-paid font-medium">Tagihan ini sudah lunas.</p>
                    </div>
                @endif
            @endcan

            {{-- Riwayat Pembayaran --}}
            <div class="bg-surface border border-line rounded overflow-hidden">
                <div class="px-4 py-3 border-b border-line">
                    <h2 class="font-display text-lg font-semibold text-ink">Riwayat Pembayaran</h2>
                </div>

                @if ($tagihan->pembayaran->isEmpty())
                    <div class="px-4 py-8 text-center text-sm text-ink-muted">
                        Belum ada pembayaran untuk tagihan ini.
                        @can('create', App\Models\Pembayaran::class)
                            @if ($sisa > 0)
                                <span class="block mt-1">Gunakan form di atas untuk mencatat pembayaran pertama.</span>
                            @endif
                        @endcan
                    </div>
                @else
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-line">
                                <th class="table-header">Tanggal</th>
                                <th class="table-header">Metode</th>
                                <th class="table-header text-right">Jumlah</th>
                                <th class="table-header">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tagihan->pembayaran as $pem)
                                <tr class="border-b border-line">
                                    <td class="table-cell font-mono">{{ $pem->tanggal_bayar->format('d/m/Y') }}</td>
                                    <td class="table-cell">{{ ucfirst($pem->metode_bayar) }}</td>
                                    <td class="table-cell text-right font-mono">Rp {{ number_format($pem->jumlah_bayar, 2) }}</td>
                                    <td class="table-cell text-ink-muted">{{ $pem->keterangan ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-line font-medium">
                                <td colspan="2" class="table-header text-right">Total</td>
                                <td class="table-cell text-right font-mono">Rp {{ number_format($totalDibayar, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
