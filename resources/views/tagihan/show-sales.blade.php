<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>{{ $tagihan->no_invoice }}</span>
            <span style="font-size:13px; padding:4px 12px; border-radius:6px;
                         background:{{ $tagihan->status_penagihan_color }}20;
                         color:{{ $tagihan->status_penagihan_color }};
                         border:1px solid {{ $tagihan->status_penagihan_color }}">
                {{ $tagihan->status_penagihan_label }}
            </span>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-surface border border-line rounded p-6 space-y-4">
                <div>
                    <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Pelanggan</p>
                    <p class="text-sm text-ink mt-1">{{ $tagihan->pelanggan->nama_pelanggan }}</p>
                </div>
                <div>
                    <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Lembaga</p>
                    <p class="text-sm text-ink mt-1">
                        {{ $tagihan->pelanggan->nama_lembaga ?: $tagihan->pelanggan->nama_pelanggan }}
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
                    <p class="text-sm text-ink font-mono mt-1">
                        {{ $tagihan->tanggal_jatuh_tempo->format('d/m/Y') }}
                        @if($tagihan->is_overdue)
                            <span class="text-xs text-status-critical">({{ $tagihan->days_overdue }} hari lewat)</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Total Tagihan</p>
                    <p class="text-lg text-ink rupiah mt-1">Rp {{ number_format($tagihan->total_tagihan, 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Sumber Dana</p>
                    <p class="text-sm text-ink mt-1">{{ $tagihan->sumber_dana ?: '-' }}</p>
                </div>

                <div class="pt-3 border-t border-line">
                    <a href="{{ route('dashboard') }}" class="btn-secondary">Kembali ke Dashboard</a>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            @if ($tagihan->items->isNotEmpty())
                <div class="bg-surface border border-line rounded overflow-hidden">
                    <div class="px-4 py-3 border-b border-line">
                        <h2 class="font-display text-lg font-semibold text-ink">Detail Item Barang</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-line">
                                    <th class="table-header">Nama Barang</th>
                                    <th class="table-header">Satuan</th>
                                    <th class="table-header text-right">Qty</th>
                                    <th class="table-header text-right">Harga</th>
                                    <th class="table-header text-right">Sub Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tagihan->items as $item)
                                    <tr class="border-b border-line">
                                        <td class="table-cell">{{ $item->nama_barang }}</td>
                                        <td class="table-cell">{{ $item->satuan ?: '-' }}</td>
                                        <td class="table-cell text-right">{{ $item->qty_netto }}</td>
                                        <td class="table-cell rupiah">Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                                        <td class="table-cell rupiah">Rp {{ number_format($item->netto_penj, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="bg-surface border border-line rounded p-6">
                <h2 class="font-display text-lg font-semibold text-ink mb-4">Update Status Penagihan</h2>
                <form method="POST" action="{{ route('tagihan.update-status', $tagihan) }}" class="space-y-4">
                    @csrf @method('PATCH')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="status_penagihan" class="block text-sm font-medium text-ink mb-1">Status</label>
                            <select id="status_penagihan" name="status_penagihan" class="input-field" required>
                                @foreach([
                                    'belum_ditagih' => 'Belum Ditagih',
                                    'sedang_ditagih' => 'Sedang Ditagih',
                                    'janji_bayar' => 'Janji Bayar',
                                    'sudah_ditagih' => 'Sudah Ditagih',
                                ] as $val => $label)
                                    <option value="{{ $val }}" {{ $tagihan->status_penagihan === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status_penagihan') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="catatan" class="block text-sm font-medium text-ink mb-1">Catatan (opsional)</label>
                            <input type="text" id="catatan" name="catatan" value="{{ old('catatan') }}" class="input-field"
                                   placeholder="Catatan kunjungan / janji bayar...">
                            @error('catatan') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Simpan Status</button>
                </form>

                @if ($tagihan->catatanPenagihan->count() > 0)
                    <div class="mt-6 pt-4 border-t border-line">
                        <p class="text-xs text-ink-muted uppercase tracking-wider font-medium mb-2">Riwayat Penagihan</p>
                        @foreach ($tagihan->catatanPenagihan as $cat)
                            <div style="padding:10px 12px; border-left:3px solid {{ $tagihan->status_penagihan_color }}; margin-bottom:8px; background:#F9F9F9">
                                <div style="display:flex; justify-content:space-between; font-size:12px; color:#5B6470">
                                    <span>{{ $cat->user->name }}</span>
                                    <span>{{ $cat->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <span style="font-size:12px; font-weight:500; color:#1B2027">{{ $cat->status_penagihan_label }}</span>
                                @if($cat->catatan)
                                    <p style="font-size:13px; margin:4px 0 0; color:#3d3d3a">{{ $cat->catatan }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
