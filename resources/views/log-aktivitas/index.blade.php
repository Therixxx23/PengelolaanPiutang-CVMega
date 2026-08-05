<x-app-layout>
    <x-slot name="header">Log Aktivitas</x-slot>

    <div class="bg-surface border border-line rounded overflow-hidden">
        <div class="px-4 py-3 border-b border-line">
            <h2 class="font-display text-lg font-semibold text-ink">Audit Trail Sistem</h2>
        </div>

        <div class="overflow-x-auto">
            <table style="width:100%; table-layout:fixed">
                <thead>
                    <tr class="border-b border-line">
                        <th style="width:16%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Waktu</th>
                        <th style="width:14%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">User</th>
                        <th style="width:18%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Aksi</th>
                        <th style="width:52%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $aksiLabel = match ($log->aksi) {
                                'buat_tagihan' => ['Buat Tagihan', '#0E6E66'],
                                'setujui_tagihan' => ['Setujui', '#3E7C58'],
                                'tolak_tagihan' => ['Tolak', '#B33A2E'],
                                'catat_pembayaran' => ['Catat Pembayaran', '#6B7CA3'],
                                default => [Str::replace('_', ' ', $log->aksi), '#5B6470'],
                            };
                        @endphp
                        <tr class="border-b border-line">
                            <td style="padding:12px 16px; font-size:13px; font-family:'IBM Plex Mono',monospace; white-space:nowrap">
                                {{ $log->created_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td style="padding:12px 16px; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" title="{{ $log->user?->name }}">
                                {{ $log->user?->name ?? '—' }}
                            </td>
                            <td style="padding:12px 16px; white-space:nowrap">
                                <span style="font-size:11px; padding:2px 8px; border-radius:4px; white-space:nowrap; background:{{ $aksiLabel[1] }}20; color:{{ $aksiLabel[1] }}; border:1px solid {{ $aksiLabel[1] }}">
                                    {{ $aksiLabel[0] }}
                                </span>
                            </td>
                            <td style="padding:12px 16px; font-size:12px; color:#5B6470">
                                <span style="font-family:'IBM Plex Mono',monospace; color:#1B2027">{{ $log->model_type }} #{{ $log->model_id }}</span>
                                @if ($log->data_sesudah)
                                    <span style="display:block; margin-top:2px">
                                        @foreach ($log->data_sesudah as $k => $v)
                                            <span style="color:#5B6470">{{ $k }}:</span>
                                            <span style="color:#1B2027">{{ is_array($v) ? json_encode($v) : $v }}</span>
                                            @if (!$loop->last) <span>, </span> @endif
                                        @endforeach
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding:32px; text-align:center; color:#5B6470; font-size:14px">
                                Belum ada aktivitas yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-line">
            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
