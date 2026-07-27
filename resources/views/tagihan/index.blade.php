<x-app-layout>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
        <h1 class="font-display text-2xl font-semibold text-ink">Tagihan</h1>
        @can('create', App\Models\Tagihan::class)
            <a href="{{ route('tagihan.create') }}"
               style="background:#0E6E66; color:white; padding:8px 16px; border-radius:6px; font-size:14px; text-decoration:none; font-family:'IBM Plex Sans',sans-serif; font-weight:500">
                + Buat Tagihan
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('tagihan.index') }}">
        <div style="display:flex; gap:8px; align-items:center; margin-bottom:16px">
            <input type="text" name="search"
                   value="{{ $search }}"
                   placeholder="Cari invoice / pelanggan..."
                   style="flex:1; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
            <select name="status"
                    style="width:160px; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
                <option value="semua" {{ $status === 'semua' ? 'selected' : '' }}>Semua Status</option>
                <option value="belum_lunas" {{ $status === 'belum_lunas' ? 'selected' : '' }}>Belum Lunas</option>
                <option value="lunas" {{ $status === 'lunas' ? 'selected' : '' }}>Lunas</option>
            </select>
            <button type="submit"
                    style="padding:8px 20px; background:#0E6E66; color:white; border:none; border-radius:6px; font-size:14px; cursor:pointer; font-family:'IBM Plex Sans',sans-serif; font-weight:500">
                Cari
            </button>
            @if($search || $status !== 'semua')
                <a href="{{ route('tagihan.index') }}"
                   style="padding:8px 16px; border:1px solid #DCE2E0; border-radius:6px; font-size:14px; color:#5B6470; font-family:Inter,sans-serif; text-decoration:none">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <p style="font-size:13px; color:#5B6470; margin-bottom:8px">
        @if($search || $status !== 'semua')
            Menampilkan {{ $tagihan->total() }} dari {{ $totalSemua }} tagihan
            @if($search) untuk "{{ $search }}"@endif
            @if($status !== 'semua') · {{ ucfirst(str_replace('_', ' ', $status)) }}@endif
        @else
            {{ $totalSemua }} tagihan
        @endif
    </p>

    <div class="bg-surface border border-line rounded overflow-hidden">
        <div class="hidden sm:block overflow-x-auto">
            <table style="width:100%; table-layout:fixed">
                <thead>
                    <tr class="border-b border-line">
                        <th style="width:22%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">No. Invoice</th>
                        <th style="width:22%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Pelanggan</th>
                        <th style="width:11%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Tanggal</th>
                        <th style="width:11%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Jatuh Tempo</th>
                        <th style="width:18%; padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Total</th>
                        <th style="width:10%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Status</th>
                        <th style="width:6%; padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tagihan as $t)
                        @php
                            $rail = match(true) {
                                $t->status === 'lunas' => 'paid',
                                $t->is_overdue => match($t->aging_bucket) {
                                    '0-30' => 'watch30',
                                    '31-60' => 'watch60',
                                    default => 'critical',
                                },
                                default => 'lancar',
                            };

                            [$label, $color] = match(true) {
                                $t->status === 'lunas'             => ['Lunas', '#3E7C58'],
                                $t->tanggal_jatuh_tempo->isPast() => ['Jatuh Tempo', '#B33A2E'],
                                default                            => ['Belum Lunas', '#C8862A'],
                            };
                            $bg = $color . '20';
                        @endphp
                        <tr class="border-b border-line hover:bg-paper transition aging-rail-{{ $rail }}">
                            <td style="padding:12px 16px; font-size:14px; overflow:hidden; text-overflow:ellipsis">
                                <a href="{{ route('tagihan.show', $t) }}"
                                   style="color:#0E6E66; text-decoration:none; font-family:'IBM Plex Mono',monospace; font-weight:500">
                                    {{ $t->no_invoice }}
                                </a>
                            </td>
                            <td style="padding:12px 16px; font-size:14px; overflow:hidden; text-overflow:ellipsis">
                                <a href="{{ route('pelanggan.show', $t->pelanggan) }}"
                                   style="color:#0E6E66; text-decoration:none">
                                    {{ $t->pelanggan->nama_pelanggan }}
                                </a>
                            </td>
                            <td style="padding:12px 16px; font-size:14px; font-family:'IBM Plex Mono',monospace">{{ $t->tanggal_tagihan->format('d/m/Y') }}</td>
                            <td style="padding:12px 16px; font-size:14px; font-family:'IBM Plex Mono',monospace">{{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}</td>
                            <td style="padding:12px 16px; font-size:14px; white-space:nowrap; text-align:right; font-family:'IBM Plex Mono',monospace">
                                Rp {{ number_format($t->total_tagihan, 2, ',', '.') }}
                            </td>
                            <td style="padding:12px 16px">
                                <span style="font-size:11px; padding:2px 8px; border-radius:4px; white-space:nowrap; background:{{ $bg }}; color:{{ $color }}; border:1px solid {{ $color }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td style="padding:12px 16px; text-align:right; white-space:nowrap">
                                <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px">
                                    @can('update', $t)
                                        <a href="{{ route('tagihan.edit', $t) }}"
                                           style="padding:4px 12px; border:1px solid #0E6E66; color:#0E6E66; background:white; border-radius:4px; font-size:12px; text-decoration:none; font-family:Inter,sans-serif; font-weight:500">
                                            Edit
                                        </a>
                                    @endcan
                                    @can('delete', $t)
                                        <form action="{{ route('tagihan.destroy', $t) }}" method="POST" onsubmit="return confirm('Hapus tagihan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    style="padding:4px 12px; border:1px solid #B33A2E; color:#B33A2E; background:white; border-radius:4px; font-size:12px; cursor:pointer; font-family:Inter,sans-serif; font-weight:500">
                                                Hapus
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:32px; text-align:center; color:#5B6470; font-size:14px">
                                @if($search || $status !== 'semua')
                                    Tidak ada tagihan yang cocok dengan pencarian ini.
                                    <a href="{{ route('tagihan.index') }}" style="color:#0E6E66; text-decoration:none">Reset pencarian</a>
                                @else
                                    Belum ada tagihan.
                                    @can('create', App\Models\Tagihan::class)
                                        <a href="{{ route('tagihan.create') }}" style="color:#0E6E66; text-decoration:none">Buat tagihan baru</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sm:hidden divide-y divide-line">
            @forelse ($tagihan as $t)
                @php
                    $railClass = match(true) {
                        $t->status === 'lunas' => 'aging-rail-paid',
                        $t->is_overdue => match($t->aging_bucket) {
                            '0-30' => 'aging-rail-watch30',
                            '31-60' => 'aging-rail-watch60',
                            default => 'aging-rail-critical',
                        },
                        default => 'aging-rail-lancar',
                    };

                    [$label, $color] = match(true) {
                        $t->status === 'lunas'             => ['Lunas', '#3E7C58'],
                        $t->tanggal_jatuh_tempo->isPast() => ['Jatuh Tempo', '#B33A2E'],
                        default                            => ['Belum Lunas', '#C8862A'],
                    };
                    $bg = $color . '20';
                @endphp
                <div class="p-4 {{ $railClass }} space-y-2">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('tagihan.show', $t) }}"
                           style="color:#0E6E66; text-decoration:none; font-family:'IBM Plex Mono',monospace; font-weight:500; font-size:14px">
                            {{ $t->no_invoice }}
                        </a>
                        <span style="font-size:11px; padding:2px 8px; border-radius:4px; white-space:nowrap; background:{{ $bg }}; color:{{ $color }}; border:1px solid {{ $color }}">
                            {{ $label }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <a href="{{ route('pelanggan.show', $t->pelanggan) }}" style="color:#0E6E66; text-decoration:none">
                            {{ $t->pelanggan->nama_pelanggan }}
                        </a>
                        <span style="font-family:'IBM Plex Mono',monospace; color:#5B6470; font-size:14px">Rp {{ number_format($t->total_tagihan, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs" style="color:#5B6470">
                        <span>Tagihan: {{ $t->tanggal_tagihan->format('d/m/Y') }}</span>
                        <span>Jatuh tempo: {{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}</span>
                    </div>
                    <div style="display:flex; gap:6px; padding-top:4px">
                        @can('update', $t)
                            <a href="{{ route('tagihan.edit', $t) }}"
                               style="padding:4px 12px; border:1px solid #0E6E66; color:#0E6E66; background:white; border-radius:4px; font-size:12px; text-decoration:none; font-family:Inter,sans-serif; font-weight:500">
                                Edit
                            </a>
                        @endcan
                        @can('delete', $t)
                            <form action="{{ route('tagihan.destroy', $t) }}" method="POST" onsubmit="return confirm('Hapus tagihan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        style="padding:4px 12px; border:1px solid #B33A2E; color:#B33A2E; background:white; border-radius:4px; font-size:12px; cursor:pointer; font-family:Inter,sans-serif; font-weight:500">
                                    Hapus
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="p-8 text-center" style="color:#5B6470; font-size:14px">
                    @if($search || $status !== 'semua')
                        Tidak ada tagihan yang cocok dengan pencarian ini.
                        <a href="{{ route('tagihan.index') }}" style="color:#0E6E66; text-decoration:none">Reset pencarian</a>
                    @else
                        Belum ada tagihan.
                        @can('create', App\Models\Tagihan::class)
                            <a href="{{ route('tagihan.create') }}" style="color:#0E6E66; text-decoration:none">Buat tagihan baru</a>
                        @endcan
                    @endif
                </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-line hidden sm:block">
            {{ $tagihan->links() }}
        </div>
    </div>
</x-app-layout>
