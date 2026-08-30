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

    <form method="GET" action="{{ route('tagihan.index') }}" id="form-filter">
        <div style="display:flex; gap:8px; align-items:center; margin-bottom:0">
            <div x-data="suggestSearch()" style="flex:1; position:relative">
                <input
                    type="text"
                    name="search"
                    x-model="query"
                    x-on:input.debounce.300ms="fetchSuggest()"
                    x-on:keydown.escape="closeSuggest()"
                    x-on:keydown.arrow-down.prevent="highlightNext()"
                    x-on:keydown.arrow-up.prevent="highlightPrev()"
                    x-on:keydown.enter.prevent="selectHighlighted()"
                    x-on:click.outside="closeSuggest()"
                    value="{{ $search }}"
                    placeholder="Cari no. invoice atau nama pelanggan..."
                    autocomplete="off"
                    style="width:100%; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66; box-sizing:border-box">

                <div x-show="open && results.length > 0"
                     x-transition
                     style="position:absolute; top:100%; left:0; right:0; background:white; border:1px solid #DCE2E0; border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,0.08); z-index:50; margin-top:4px; overflow:hidden">

                    <template x-for="(item, index) in results" :key="index">
                        <div
                            x-on:click="selectItem(item)"
                            x-on:mouseenter="highlighted = index"
                            :style="highlighted === index
                                ? 'background:#F0FAF9; cursor:pointer; padding:8px 12px'
                                : 'cursor:pointer; padding:8px 12px'"
                            style="border-bottom:1px solid #DCE2E0; display:flex; align-items:center; gap:8px">
                            <span :style="item.type === 'invoice'
                                    ? 'font-size:10px; padding:2px 6px; border-radius:4px; background:#0E6E6620; color:#0E6E66; white-space:nowrap'
                                    : 'font-size:10px; padding:2px 6px; border-radius:4px; background:#6B7CA320; color:#6B7CA3; white-space:nowrap'"
                                  x-text="item.type === 'invoice' ? 'Invoice' : 'Pelanggan'">
                            </span>
                            <span style="font-size:14px; color:#1B2027" x-text="item.label"></span>
                        </div>
                    </template>
                </div>
            </div>

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
            @if($search || $status !== 'semua' || $sumber_dana !== 'semua' || $sales !== 'semua' || $penagihan !== 'semua')
                <a href="{{ route('tagihan.index') }}"
                   style="padding:8px 16px; border:1px solid #DCE2E0; border-radius:6px; font-size:14px; color:#5B6470; font-family:Inter,sans-serif; text-decoration:none">
                    Reset
                </a>
            @endif
        </div>

        <div style="display:flex; gap:12px; align-items:center; margin-top:8px; flex-wrap:wrap">
            <div style="display:flex; align-items:center; gap:6px">
                <label style="font-size:12px; color:#5B6470">Dana:</label>
                <select name="sumber_dana" form="form-filter"
                        style="width:140px; padding:6px 10px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:13px; color:#1B2027; outline-color:#0E6E66">
                    <option value="semua" {{ $sumber_dana === 'semua' ? 'selected' : '' }}>Semua</option>
                    @foreach($daftarSumber as $s)
                        <option value="{{ $s }}" {{ $sumber_dana === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex; align-items:center; gap:6px">
                <label style="font-size:12px; color:#5B6470">Sales:</label>
                <select name="sales" form="form-filter"
                        style="width:180px; padding:6px 10px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:13px; color:#1B2027; outline-color:#0E6E66">
                    <option value="semua" {{ $sales === 'semua' ? 'selected' : '' }}>Semua</option>
                    @foreach($daftarSales as $sl)
                        <option value="{{ $sl }}" {{ $sales === $sl ? 'selected' : '' }}>{{ $sl }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex; align-items:center; gap:6px">
                <label style="font-size:12px; color:#5B6470">Penagihan:</label>
                <select name="penagihan" form="form-filter"
                        style="width:160px; padding:6px 10px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:13px; color:#1B2027; outline-color:#0E6E66">
                    <option value="semua" {{ $penagihan === 'semua' ? 'selected' : '' }}>Semua</option>
                    <option value="belum_ditagih" {{ $penagihan === 'belum_ditagih' ? 'selected' : '' }}>Belum Ditagih</option>
                    <option value="sedang_ditagih" {{ $penagihan === 'sedang_ditagih' ? 'selected' : '' }}>Sedang Ditagih</option>
                    <option value="janji_bayar" {{ $penagihan === 'janji_bayar' ? 'selected' : '' }}>Janji Bayar</option>
                    <option value="sudah_ditagih" {{ $penagihan === 'sudah_ditagih' ? 'selected' : '' }}>Sudah Ditagih</option>
                </select>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
    function suggestSearch() {
        return {
            query: @js($search),
            results: [],
            open: false,
            highlighted: -1,

            async fetchSuggest() {
                if (this.query.length < 2) {
                    this.results = [];
                    this.open = false;
                    return;
                }
                try {
                    const res = await fetch(
                        `{{ route('tagihan.suggest') }}?q=${encodeURIComponent(this.query)}`
                    );
                    this.results = await res.json();
                    this.open = this.results.length > 0;
                    this.highlighted = -1;
                } catch (e) {
                    this.results = [];
                    this.open = false;
                }
            },

            selectItem(item) {
                this.query = item.label;
                this.open = false;
                this.$nextTick(() => {
                    this.$el.closest('form').submit();
                });
            },

            highlightNext() {
                if (this.highlighted < this.results.length - 1)
                    this.highlighted++;
            },

            highlightPrev() {
                if (this.highlighted > 0) this.highlighted--;
            },

            selectHighlighted() {
                if (this.highlighted >= 0 && this.results[this.highlighted]) {
                    this.selectItem(this.results[this.highlighted]);
                }
            },

            closeSuggest() {
                this.open = false;
                this.highlighted = -1;
            }
        }
    }
    </script>
    @endpush

    <p style="font-size:13px; color:#5B6470; margin-bottom:8px">
        @if($search || $status !== 'semua' || $sumber_dana !== 'semua' || $sales !== 'semua' || $penagihan !== 'semua')
            Menampilkan {{ $tagihan->total() }} dari {{ $totalSemua }} tagihan
            @if($search) untuk "{{ $search }}"@endif
            @if($status !== 'semua') · {{ ucfirst(str_replace('_', ' ', $status)) }}@endif
            @if($sumber_dana !== 'semua') · Dana: {{ $sumber_dana }}@endif
            @if($sales !== 'semua') · Sales: {{ $sales }}@endif
            @if($penagihan !== 'semua') · Penagihan: {{ ucfirst(str_replace('_', ' ', $penagihan)) }}@endif
        @else
            {{ $totalSemua }} tagihan
        @endif
    </p>

    <div class="bg-surface border border-line rounded overflow-hidden">
        <div class="hidden sm:block overflow-x-auto">
            <style>
                .tabel-tagihan th {
                    padding: 10px 8px;
                    font-size: 11px;
                    color: #5B6470;
                    font-weight: 600;
                    letter-spacing: 0.04em;
                    text-transform: uppercase;
                    border-bottom: 2px solid #DCE2E0;
                    background: #F6F7F6;
                    text-align: left;
                    white-space: nowrap;
                }
                .tabel-tagihan td {
                    padding: 8px 6px;
                    border-bottom: 1px solid #DCE2E0;
                    vertical-align: middle;
                    font-size: 13px;
                }
                .tabel-tagihan tr:hover td {
                    background: #FAFAFA;
                }
            </style>
            <table style="width:100%; min-width:1100px; table-layout:fixed; border-collapse:collapse" class="tabel-tagihan">
                <thead>
                    <tr>
                        <th style="width:13%">No. Invoice</th>
                        <th style="width:7%">No. SJ</th>
                        <th style="width:15%">Lembaga</th>
                        <th style="width:9%">Tanggal</th>
                        <th style="width:9%">Jatuh Tempo</th>
                        <th style="width:10%">Sales</th>
                        <th style="width:6%">Dana</th>
                        <th style="width:10%; text-align:right">Total</th>
                        <th style="width:10%">Status</th>
                        <th style="width:7%">Penagihan</th>
                        <th style="width:4%; text-align:right"></th>
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
                        <tr class="aging-rail-{{ $rail }}">
                            <td style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0"
                                title="{{ $t->no_invoice }}">
                                <a href="{{ route('tagihan.show', $t) }}"
                                   style="color:#0E6E66; text-decoration:none; font-family:'IBM Plex Mono',monospace; font-weight:500">
                                    {{ $t->no_invoice }}
                                </a>
                            </td>
                            <td style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0; font-family:'IBM Plex Mono',monospace; font-size:11px; color:#5B6470"
                                title="{{ $t->no_sj ?: '-' }}">
                                {{ $t->no_sj ?: '-' }}
                            </td>
                            <td style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0"
                                title="{{ $t->pelanggan->nama_lembaga ?: $t->pelanggan->nama_pelanggan }}">
                                <a href="{{ route('pelanggan.show', $t->pelanggan) }}"
                                   style="color:#0E6E66; text-decoration:none">
                                    {{ $t->pelanggan->nama_lembaga ?: $t->pelanggan->nama_pelanggan }}
                                </a>
                            </td>
                            <td style="white-space:nowrap; font-size:13px; font-family:'IBM Plex Mono',monospace">{{ $t->tanggal_tagihan->format('d/m/Y') }}</td>
                            <td style="white-space:nowrap; font-size:13px; font-family:'IBM Plex Mono',monospace; color:{{ $t->tanggal_jatuh_tempo->isPast() && $t->status === 'belum_lunas' ? '#B33A2E' : '#1B2027' }}">{{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}</td>
                            <td style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0; font-size:12px; color:#5B6470"
                                title="{{ $t->nama_sales ?: '-' }}">
                                {{ $t->nama_sales ?: '-' }}
                            </td>
                            <td style="white-space:nowrap; padding:8px 6px">
                                <x-badge-sumber-dana :sumber="$t->sumber_dana" />
                            </td>
                            <td style="text-align:right; white-space:nowrap; font-size:13px">
                                <span style="color:#5B6470; margin-right:2px">Rp</span>
                                <span style="font-family:'IBM Plex Mono',monospace">{{ number_format($t->total_tagihan, 0, ',', '.') }}</span>
                            </td>
                            <td style="white-space:nowrap; padding:8px 6px">
                                <span style="font-size:11px; padding:3px 8px; border-radius:4px; white-space:nowrap; background:{{ $bg }}; color:{{ $color }}; border:1px solid {{ $color }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td style="white-space:nowrap; padding:8px 6px">
                                @if($t->status_penagihan)
                                    <x-badge-penagihan :status="$t->status_penagihan" />
                                @endif
                            </td>
                            <td style="white-space:nowrap; padding:8px 6px; text-align:right">
                                <div style="display:flex; gap:4px; align-items:center; justify-content:flex-end">
                                    @can('update', $t)
                                        <a href="{{ route('tagihan.edit', $t) }}"
                                           style="font-size:12px; padding:4px 10px; border:1px solid #0E6E66; color:#0E6E66; background:white; border-radius:4px; text-decoration:none; white-space:nowrap">
                                            Edit
                                        </a>
                                    @endcan
                                    @can('delete', $t)
                                        <button onclick="confirm('Hapus tagihan ini?') || event.preventDefault()"
                                                form="form-hapus-{{ $t->id_tagihan }}"
                                                style="font-size:12px; padding:4px 10px; border:1px solid #B33A2E; color:#B33A2E; background:white; border-radius:4px; cursor:pointer; white-space:nowrap">
                                            Hapus
                                        </button>
                                        <form id="form-hapus-{{ $t->id_tagihan }}" method="POST"
                                              action="{{ route('tagihan.destroy', $t) }}" style="display:none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="padding:32px; text-align:center; color:#5B6470; font-size:14px">
                                @if($search || $status !== 'semua' || $sumber_dana !== 'semua' || $sales !== 'semua' || $penagihan !== 'semua')
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
                            {{ $t->pelanggan->nama_lembaga ?: $t->pelanggan->nama_pelanggan }}
                        </a>
                        <span style="font-family:'IBM Plex Mono',monospace; color:#5B6470; font-size:14px">Rp {{ number_format($t->total_tagihan, 2, ',', '.') }}</span>
                    </div>
                    @if($t->no_sj || $t->nama_sales || $t->sumber_dana)
                        <div style="display:flex; align-items:center; justify-content:space-between; font-size:12px; color:#5B6470; flex-wrap:wrap; gap:6px">
                            <span>
                                <span style="font-family:'IBM Plex Mono',monospace">{{ $t->no_sj ?: '-' }}</span>
                                @if($t->nama_sales)
                                    · {{ $t->nama_sales }}
                                @endif
                            </span>
                            <x-badge-sumber-dana :sumber="$t->sumber_dana" />
                        </div>
                    @endif
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
                    @if($search || $status !== 'semua' || $sumber_dana !== 'semua' || $sales !== 'semua' || $penagihan !== 'semua')
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
