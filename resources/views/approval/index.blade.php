<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>Persetujuan Tagihan</span>
            <span class="badge {{ $tagihan->total() > 0 ? 'badge-watch30' : 'badge-paid' }}">
                {{ $tagihan->total() }} menunggu review
            </span>
        </div>
    </x-slot>

    <div x-data="{
        open: false,
        tagihanId: null,
        actionUrl: ''
    }"
    x-on:open-tolak.window="
        open = true;
        tagihanId = $event.detail.id;
        actionUrl = '/approval/' + tagihanId + '/tolak'
    ">

        <div class="bg-surface border border-line rounded overflow-hidden">
            @if ($tagihan->isEmpty())
                <div class="px-4 py-12 text-center text-sm text-ink-muted">
                    Tidak ada tagihan yang menunggu persetujuan.
                </div>
            @else
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-line">
                                <th class="table-header">No. Invoice</th>
                                <th class="table-header">Pelanggan</th>
                                <th class="table-header text-right">Total</th>
                                <th class="table-header">Tanggal Dibuat</th>
                                <th class="table-header text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tagihan as $t)
                                <tr class="border-b border-line">
                                    <td class="table-cell font-mono">{{ $t->no_invoice }}</td>
                                    <td class="table-cell">{{ $t->pelanggan?->nama_pelanggan }}</td>
                                    <td class="table-cell rupiah">Rp {{ number_format($t->total_tagihan, 2, ',', '.') }}</td>
                                    <td class="table-cell font-mono">{{ $t->created_at->format('d/m/Y') }}</td>
                                    <td class="table-cell">
                                        <div class="flex gap-2 justify-end">
                                            <button type="button"
                                                x-on:click="if (confirm('Yakin menyetujui tagihan ini?')) $refs.formSetujui{{ $t->id_tagihan }}.submit()"
                                                class="btn-primary" style="padding:6px 14px; font-size:13px">
                                                ✓ Setujui
                                            </button>
                                            <form x-ref="formSetujui{{ $t->id_tagihan }}" method="POST"
                                                action="{{ route('approval.setujui', $t) }}" style="display:none">
                                                @csrf
                                            </form>
                                            <button type="button"
                                                x-on:click="$dispatch('open-tolak', {id: {{ $t->id_tagihan }}})"
                                                class="btn-destructive" style="padding:6px 14px; font-size:13px">
                                                ✕ Tolak
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="sm:hidden divide-y divide-line">
                    @foreach ($tagihan as $t)
                        <div class="p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-mono">{{ $t->no_invoice }}</span>
                                <span class="rupiah text-sm">Rp {{ number_format($t->total_tagihan, 2, ',', '.') }}</span>
                            </div>
                            <p class="text-xs text-ink-muted">{{ $t->pelanggan?->nama_pelanggan }} &middot; dibuat {{ $t->created_at->format('d/m/Y') }}</p>
                            <div class="flex gap-2">
                                <button type="button"
                                    x-on:click="if (confirm('Yakin menyetujui tagihan ini?')) $refs.formSetujui{{ $t->id_tagihan }}.submit()"
                                    class="btn-primary" style="padding:6px 14px; font-size:13px">
                                    ✓ Setujui
                                </button>
                                <form x-ref="formSetujui{{ $t->id_tagihan }}" method="POST"
                                    action="{{ route('approval.setujui', $t) }}" style="display:none">
                                    @csrf
                                </form>
                                <button type="button"
                                    x-on:click="$dispatch('open-tolak', {id: {{ $t->id_tagihan }}})"
                                    class="btn-destructive" style="padding:6px 14px; font-size:13px">
                                    ✕ Tolak
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($tagihan->hasPages())
            <div class="mt-6">
                {{ $tagihan->links() }}
            </div>
        @endif

        {{-- Modal penolakan (satu modal untuk semua baris) --}}
        <div x-show="open"
            style="position:fixed; inset:0; background:rgba(0,0,0,0.4);
                   z-index:100; display:flex; align-items:center;
                   justify-content:center">

            <div style="background:white; border-radius:8px; padding:24px;
                        width:480px; max-width:90vw">
                <h3 style="margin:0 0 16px; color:#1B2027">Alasan Penolakan</h3>
                <form method="POST" x-bind:action="actionUrl">
                    @csrf
                    <textarea name="approval_note"
                        placeholder="Jelaskan alasan penolakan tagihan ini (minimal 10 karakter)..."
                        required
                        style="width:100%; height:120px; padding:10px;
                               border:1px solid #DCE2E0; border-radius:6px;
                               font-size:14px; resize:vertical;
                               outline-color:#B33A2E; box-sizing:border-box"></textarea>
                    @error('approval_note')
                        <p class="mt-1 text-sm text-status-critical">{{ $message }}</p>
                    @enderror
                    <div style="display:flex; gap:8px; justify-content:flex-end;
                                margin-top:12px">
                        <button type="button"
                            x-on:click="open = false"
                            style="padding:8px 16px; border:1px solid #DCE2E0;
                                   border-radius:6px; background:white;
                                   color:#5B6470; cursor:pointer">
                            Batal
                        </button>
                        <button type="submit"
                            style="padding:8px 16px; background:#B33A2E;
                                   color:white; border:none; border-radius:6px;
                                   cursor:pointer">
                            Tolak Tagihan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
