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

    @if($tagihan->approval_status === 'menunggu_persetujuan')
    <div style="padding:16px; background:#FFF8F0;
                border:1px solid #C8862A; border-radius:8px;
                border-left:4px solid #C8862A; margin-bottom:20px">
        <p style="font-weight:600; color:#C8862A; margin:0">
            ⏳ Menunggu Persetujuan Pimpinan
        </p>
        <p style="font-size:13px; color:#5B6470; margin:4px 0 0">
            Tagihan ini melebihi threshold dan sedang menunggu
            persetujuan. Pembayaran belum bisa dicatat sampai
            Pimpinan menyetujui.
        </p>
    </div>

    @elseif($tagihan->approval_status === 'ditolak')
    <div style="padding:16px; background:#FFF5F5;
                border:1px solid #B33A2E; border-radius:8px;
                border-left:4px solid #B33A2E; margin-bottom:20px">
        <p style="font-weight:600; color:#B33A2E; margin:0">
            ✕ Tagihan Ditolak
        </p>
        <p style="font-size:13px; color:#5B6470; margin:4px 0 0">
            Ditolak oleh {{ $tagihan->approvedBy->name }}
            pada {{ $tagihan->approved_at->format('d/m/Y H:i') }}
        </p>
        <p style="font-size:13px; color:#B33A2E; margin:4px 0 0;
                  font-style:italic">
            Alasan: {{ $tagihan->approval_note }}
        </p>
    </div>

    @elseif($tagihan->approval_status === 'aktif'
            && $tagihan->approved_by)
    <div style="padding:12px 16px; background:#F0FAF5;
                border:1px solid #3E7C58; border-radius:8px;
                border-left:4px solid #3E7C58; margin-bottom:20px">
        <p style="font-size:13px; color:#3E7C58; margin:0">
            ✓ Disetujui oleh {{ $tagihan->approvedBy->name }}
            pada {{ $tagihan->approved_at->format('d/m/Y H:i') }}
        </p>
    </div>
    @endif

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
                        <p class="text-lg text-ink rupiah mt-1">Rp {{ number_format($tagihan->total_tagihan, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        @php
                            $totalDibayar = $tagihan->pembayaran->sum('jumlah_bayar');
                            $sisa = $tagihan->total_tagihan - $totalDibayar;
                        @endphp
                        <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Total Dibayar</p>
                        <p class="text-sm text-ink rupiah mt-1">Rp {{ number_format($totalDibayar, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted uppercase tracking-wider font-medium">Sisa Tagihan</p>
                        <p class="text-sm {{ $sisa > 0 ? 'text-status-watch30' : 'text-status-paid' }} rupiah mt-1">Rp {{ number_format(max(0, $sisa), 2, ',', '.') }}</p>
                    </div>

                    <div class="pt-3 border-t border-line flex gap-2">
                        @can('update', $tagihan)
                            <a href="{{ route('tagihan.edit', $tagihan) }}" class="btn-edit">Edit</a>
                        @endcan
                        @can('update', $tagihan)
                            <a href="{{ route('tagihan.pdf', $tagihan) }}" class="btn-secondary">Unduh Surat Tagihan</a>
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
                @if ($tagihan->bisa_dibayar)
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
                                    Jumlah Bayar (Rp) — maksimal Rp {{ number_format($sisa, 2, ',', '.') }}
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
                @elseif ($tagihan->status === 'lunas')
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
                            @if ($tagihan->bisa_dibayar)
                                <span class="block mt-1">Gunakan form di atas untuk mencatat pembayaran pertama.</span>
                            @endif
                        @endcan
                    </div>
                @else
                    <div class="hidden sm:block overflow-x-auto">
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
                                        <td class="table-cell rupiah">Rp {{ number_format($pem->jumlah_bayar, 2, ',', '.') }}</td>
                                        <td class="table-cell text-ink-muted">{{ $pem->keterangan ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-line font-medium">
                                    <td colspan="2" class="table-header text-right">Total</td>
                                    <td class="table-cell rupiah">Rp {{ number_format($totalDibayar, 2, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="sm:hidden divide-y divide-line">
                        @foreach ($tagihan->pembayaran as $pem)
                            <div class="p-4 flex items-center justify-between">
                                <div class="space-y-1">
                                    <p class="text-sm font-mono">{{ $pem->tanggal_bayar->format('d/m/Y') }}</p>
                                    <p class="text-xs text-ink-muted">{{ ucfirst($pem->metode_bayar) }}{{ $pem->keterangan ? ' — '.$pem->keterangan : '' }}</p>
                                </div>
                                <span class="rupiah text-sm">Rp {{ number_format($pem->jumlah_bayar, 2, ',', '.') }}</span>
                            </div>
                        @endforeach
                        <div class="p-4 flex items-center justify-between font-medium border-t border-line">
                            <span class="text-sm">Total</span>
                            <span class="rupiah text-sm">Rp {{ number_format($totalDibayar, 2, ',', '.') }}</span>
                        </div>
                    </div>
                @endif
            </div>
            {{-- Detail Item Barang (impor SIPLAH) --}}
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
                                    <th class="table-header text-right">Diskon</th>
                                    <th class="table-header text-right">Sub Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tagihan->items as $item)
                                    <tr class="border-b border-line">
                                        <td class="table-cell">
                                            {{ $item->nama_barang }}
                                            @if ($item->kelas)
                                                <span class="block text-xs text-ink-muted">{{ $item->kelas }}</span>
                                            @endif
                                        </td>
                                        <td class="table-cell">{{ $item->satuan ?: '-' }}</td>
                                        <td class="table-cell text-right">{{ $item->qty_netto }}</td>
                                        <td class="table-cell rupiah">Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                                        <td class="table-cell rupiah">{{ $item->persen_diskon ?: '-' }}</td>
                                        <td class="table-cell rupiah">Rp {{ number_format($item->netto_penj, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Status & Riwayat Penagihan --}}
    <div style="margin-top:24px; border:1px solid #DCE2E0; border-radius:8px; padding:20px">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px">
            <h3 style="margin:0; font-size:15px; color:#1B2027">Status Penagihan</h3>
            @can('update', $tagihan)
                <span style="font-size:12px; color:#5B6470">
                    {{ $tagihan->assignedSales ? 'Assigned ke: '.$tagihan->assignedSales->name : 'Belum di-assign ke sales' }}
                </span>
            @endcan
        </div>

        <div style="margin-top:12px">
            <span style="font-size:13px; padding:4px 12px; border-radius:6px;
                         background:{{ $tagihan->status_penagihan_color }}20;
                         color:{{ $tagihan->status_penagihan_color }};
                         border:1px solid {{ $tagihan->status_penagihan_color }}">
                {{ $tagihan->status_penagihan_label }}
            </span>
        </div>

        @can('updatePenagihan', $tagihan)
            <form method="POST" action="{{ route('tagihan.update-status', $tagihan) }}"
                  style="margin-top:16px">
                @csrf @method('PATCH')
                <div style="display:flex; gap:8px; align-items:flex-start; flex-wrap:wrap">
                    <select name="status_penagihan"
                            style="padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-size:14px; color:#1B2027; background:white">
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
                    <input type="text" name="catatan" placeholder="Catatan (opsional)..."
                           value="{{ old('catatan') }}"
                           style="flex:1; min-width:200px; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-size:14px; color:#1B2027">
                    <button type="submit"
                            style="padding:8px 16px; background:#0E6E66; color:white; border:none; border-radius:6px; cursor:pointer; font-size:14px">
                        Update
                    </button>
                </div>
                @error('status_penagihan') <p style="color:#B33A2E; font-size:13px; margin:8px 0 0">{{ $message }}</p> @enderror
            </form>
        @endcan

        @can('update', $tagihan)
            <div style="margin-top:16px; padding-top:16px; border-top:1px solid #EEF0EF">
                <p style="font-size:12px; color:#5B6470; margin:0 0 8px">Assign ke Sales:</p>
                <form method="POST" action="{{ route('tagihan.assign-sales', $tagihan) }}"
                      style="display:flex; gap:8px">
                    @csrf @method('PATCH')
                    <select name="sales_id"
                            style="padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-size:14px; color:#1B2027; background:white">
                        <option value="">-- Belum di-assign --</option>
                        @foreach(\App\Models\User::where('role','sales')->where('is_active',true)->get() as $s)
                            <option value="{{ $s->id }}" {{ $tagihan->assigned_sales_id == $s->id ? 'selected' : '' }}>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit"
                            style="padding:8px 16px; border:1px solid #0E6E66; color:#0E6E66; background:white; border-radius:6px; cursor:pointer; font-size:14px">
                        Assign
                    </button>
                </form>
            </div>
        @endcan

        @if ($tagihan->catatanPenagihan->count() > 0)
            <div style="margin-top:16px">
                <p style="font-size:12px; color:#5B6470; margin-bottom:8px; font-weight:500">Riwayat Penagihan</p>
                @foreach ($tagihan->catatanPenagihan as $cat)
                    <div style="padding:10px 12px; border-left:3px solid {{ $tagihan->status_penagihan_color }}; margin-bottom:8px; background:#F9F9F9">
                        <div style="display:flex; justify-content:space-between; font-size:12px; color:#5B6470">
                            <span>{{ $cat->user->name }}</span>
                            <span>{{ $cat->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <span style="font-size:12px; font-weight:500; color:#1B2027">
                            {{ $cat->status_penagihan_label ?? $cat->status_penagihan }}
                        </span>
                        @if($cat->catatan)
                            <p style="font-size:13px; margin:4px 0 0; color:#3d3d3a">{{ $cat->catatan }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
