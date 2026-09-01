<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>Bukti Pembayaran</span>
            @can('create', App\Models\PembayaranBukti::class)
                <a href="{{ route('pembayaran-bukti.create') }}" class="btn-primary">Unggah Bukti</a>
            @endcan
        </div>
    </x-slot>

    @if(auth()->user()->isKeuangan() || auth()->user()->isAdministrasi())
        <div style="display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap">
            <div style="flex:1; border:1px solid #E5C99B; border-radius:8px; padding:16px; background:#FFFBF5">
                <div style="font-size:11px; color:#C8862A; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Menunggu Validasi
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#B8612A; font-weight:600">
                    {{ App\Models\PembayaranBukti::where('status','pending')->count() }}
                </div>
            </div>
            <div style="flex:1; border:1px solid #BFD8C8; border-radius:8px; padding:16px; background:#F6FBF8">
                <div style="font-size:11px; color:#3E7C58; font-weight:600; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:4px">
                    Total Nilai Menunggu Validasi
                </div>
                <div style="font-family:'IBM Plex Mono',monospace; font-size:20px; color:#3E7C58; font-weight:600">
                    Rp {{ number_format((float) App\Models\PembayaranBukti::where('status','pending')->sum('nominal_dibayar'), 0, ',', '.') }}
                </div>
            </div>
        </div>
    @endif

    <div class="mb-4 flex items-center gap-2 flex-wrap">
        <form method="GET" action="{{ route('pembayaran-bukti.index') }}" class="flex items-center gap-2">
            <select name="filter" class="input-field !w-auto" onchange="this.form.submit()">
                <option value="semua" {{ $filter === 'semua' ? 'selected' : '' }}>Semua Status</option>
                <option value="pending" {{ $filter === 'pending' ? 'selected' : '' }}>Menunggu Validasi</option>
                <option value="approved" {{ $filter === 'approved' ? 'selected' : '' }}>Disetujui</option>
                <option value="rejected" {{ $filter === 'rejected' ? 'selected' : '' }}>Ditolak</option>
            </select>
            <noscript><button type="submit" class="btn-secondary">Filter</button></noscript>
        </form>
    </div>

    <div class="bg-surface border border-line rounded overflow-hidden">
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-line">
                        <th class="table-header">No. Invoice</th>
                        <th class="table-header">Pelanggan</th>
                        <th class="table-header">Sales</th>
                        <th class="table-header">Tanggal Bayar</th>
                        <th class="table-header text-right">Nominal</th>
                        <th class="table-header">Status</th>
                        <th class="table-header">Bukti / Catatan</th>
                        <th class="table-header text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bukti as $b)
                        @php
                            $slotStatus = match ($b->status) {
                                'pending' => ['Menunggu Validasi', '#C8862A'],
                                'approved' => ['Disetujui', '#3E7C58'],
                                'rejected' => ['Ditolak', '#B33A2E'],
                                default => [$b->status, '#5B6470'],
                            };
                        @endphp
                        <tr class="border-b border-line">
                            <td class="table-cell font-mono">
                                <a href="{{ route('tagihan.show', $b->tagihan) }}" class="text-action hover:underline">
                                    {{ $b->tagihan->no_invoice }}
                                </a>
                            </td>
                            <td class="table-cell">{{ $b->tagihan->pelanggan->nama_pelanggan }}</td>
                            <td class="table-cell">{{ $b->sales->name }}</td>
                            <td class="table-cell font-mono">{{ $b->tanggal_bayar->format('d/m/Y') }}</td>
                            <td class="table-cell rupiah text-sm">Rp {{ number_format($b->nominal_dibayar, 2, ',', '.') }}</td>
                            <td class="table-cell">
                                <span class="text-[11px] px-2 py-0.5 rounded font-medium"
                                      style="background:{{ $slotStatus[1] }}20; color:{{ $slotStatus[1] }}; border:1px solid {{ $slotStatus[1] }}">
                                    {{ $slotStatus[0] }}
                                </span>
                            </td>
                            <td class="table-cell text-sm">
                                @if ($b->file_path)
                                    <a href="{{ route('pembayaran-bukti.download', $b) }}" target="_blank" class="text-action hover:underline">Lihat file</a>
                                @endif
                                @if ($b->catatan_reject)
                                    <p class="text-xs text-status-critical mt-1 italic">{{ $b->catatan_reject }}</p>
                                @endif
                                @if ($b->validated_at)
                                    <p class="text-xs text-ink-muted mt-1">
                                        Divalidasi {{ $b->validator?->name }} {{ $b->validated_at->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                            </td>
                            <td class="table-cell text-right whitespace-nowrap">
                                @can('approve', $b)
                                    <form method="POST" action="{{ route('pembayaran-bukti.setujui', $b) }}" class="inline"
                                          onsubmit="return confirm('Setujui bukti pembayaran ini? Pembayaran akan dicatat otomatis.')">
                                        @csrf
                                        <button type="submit" class="btn-edit !px-3 !py-1 !text-xs">Setujui</button>
                                    </form>
                                    <button type="button" class="btn-destructive !px-3 !py-1 !text-xs"
                                            onclick="document.getElementById('reject-form-{{ $b->id }}').classList.toggle('hidden')">
                                        Tolak
                                    </button>
                                @endcan
                                @can('delete', $b)
                                    <form method="POST" action="{{ route('pembayaran-bukti.destroy', $b) }}" class="inline"
                                          onsubmit="return confirm('Hapus bukti pembayaran ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-secondary !px-3 !py-1 !text-xs">Hapus</button>
                                    </form>
                                @endcan

                                @cannot('approve', $b)
                                    @if ($b->status === 'approved' || $b->status === 'rejected')
                                        <span class="inline-flex items-center gap-1 text-xs text-ink-muted">
                                            @if ($b->status === 'approved')
                                                <span class="text-[10px] px-2 py-0.5 rounded font-medium"
                                                      style="background:#3E7C5820; color:#3E7C58; border:1px solid #3E7C58">Disetujui</span>
                                            @else
                                                <span class="text-[10px] px-2 py-0.5 rounded font-medium"
                                                      style="background:#B33A2E20; color:#B33A2E; border:1px solid #B33A2E">Ditolak</span>
                                            @endif
                                            <span class="whitespace-nowrap">oleh {{ $b->validator?->name ?? '-' }}
                                                · {{ $b->validated_at?->format('d/m/Y') ?? '-' }}</span>
                                        </span>
                                    @else
                                        <span class="text-xs text-ink-muted">&mdash;</span>
                                    @endif
                                @endcannot
                            </td>
                        </tr>
                        @can('reject', $b)
                        <tr id="reject-form-{{ $b->id }}" class="hidden border-b border-line">
                            <td colspan="8" class="px-4 py-3">
                                <form method="POST" action="{{ route('pembayaran-bukti.tolak', $b) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="text" name="catatan_reject" required minlength="5"
                                           placeholder="Alasan penolakan (min 5 karakter)"
                                           class="input-field flex-1">
                                    <button type="submit" class="btn-destructive !px-3 !py-1 !text-xs">Tolak Bukti</button>
                                    <button type="button" class="btn-secondary !px-3 !py-1 !text-xs"
                                            onclick="document.getElementById('reject-form-{{ $b->id }}').classList.add('hidden')">Batal</button>
                                </form>
                                @error('catatan_reject') <p class="mt-1 text-sm text-status-critical">{{ $message }}</p> @enderror
                            </td>
                        </tr>
                        @endcan
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-ink-muted">
                                @if(auth()->user()->isSales())
                                    Belum ada bukti pembayaran yang Anda unggah.
                                @else
                                    Belum ada bukti pembayaran.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sm:hidden divide-y divide-line">
            @forelse ($bukti as $b)
                @php
                    $slotStatus = match ($b->status) {
                        'pending' => ['Menunggu Validasi', '#C8862A'],
                        'approved' => ['Disetujui', '#3E7C58'],
                        'rejected' => ['Ditolak', '#B33A2E'],
                        default => [$b->status, '#5B6470'],
                    };
                @endphp
                <div class="p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-sm font-medium">{{ $b->tagihan->no_invoice }}</span>
                        <span class="text-[11px] px-2 py-0.5 rounded font-medium"
                              style="background:{{ $slotStatus[1] }}20; color:{{ $slotStatus[1] }}; border:1px solid {{ $slotStatus[1] }}">
                            {{ $slotStatus[0] }}
                        </span>
                    </div>
                    <div class="text-sm">{{ $b->tagihan->pelanggan->nama_pelanggan }}</div>
                    <div class="flex items-center justify-between text-sm">
                        <span>{{ $b->sales->name }} · {{ $b->tanggal_bayar->format('d/m/Y') }}</span>
                        <span class="rupiah">Rp {{ number_format($b->nominal_dibayar, 2, ',', '.') }}</span>
                    </div>
                    @if ($b->catatan_reject)
                        <p class="text-xs text-status-critical italic">{{ $b->catatan_reject }}</p>
                    @endif
                    <div class="flex items-center gap-2">
                        @if ($b->file_path)
                            <a href="{{ route('pembayaran-bukti.download', $b) }}" target="_blank" class="text-sm text-action hover:underline">Lihat file</a>
                        @endif
                        @can('approve', $b)
                            <form method="POST" action="{{ route('pembayaran-bukti.setujui', $b) }}" class="inline"
                                  onsubmit="return confirm('Setujui bukti ini?')">
                                @csrf
                                <button type="submit" class="btn-edit !px-3 !py-1 !text-xs">Setujui</button>
                            </form>
                        @endcan
                        @can('delete', $b)
                            <form method="POST" action="{{ route('pembayaran-bukti.destroy', $b) }}" class="inline"
                                  onsubmit="return confirm('Hapus bukti pembayaran ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-secondary !px-3 !py-1 !text-xs">Hapus</button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-ink-muted">Belum ada bukti pembayaran.</div>
            @endforelse
        </div>

        @if ($bukti->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $bukti->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
