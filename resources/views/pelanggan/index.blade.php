<x-app-layout>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
        <h1 class="font-display text-2xl font-semibold text-ink">Pelanggan</h1>
        @can('create', App\Models\Pelanggan::class)
            <a href="{{ route('pelanggan.create') }}"
               style="background:#0E6E66; color:white; padding:8px 16px; border-radius:6px; font-size:14px; text-decoration:none; font-family:'IBM Plex Sans',sans-serif; font-weight:500">
                + Tambah Pelanggan
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('pelanggan.index') }}">
        <div style="display:flex; gap:8px; align-items:center; margin-bottom:12px">
            <div x-data="suggestPelanggan()" style="flex:1; position:relative">
                <input type="text" name="search"
                    x-model="query"
                    x-on:input.debounce.300ms="fetchSuggest()"
                    x-on:keydown.escape="closeSuggest()"
                    x-on:keydown.arrow-down.prevent="highlightNext()"
                    x-on:keydown.arrow-up.prevent="highlightPrev()"
                    x-on:keydown.enter.prevent="selectHighlighted()"
                    x-on:click.outside="closeSuggest()"
                    value="{{ $search }}"
                    placeholder="Cari nama pelanggan atau wilayah..."
                    autocomplete="off"
                    style="width:100%; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66; box-sizing:border-box">

                <div x-show="open && results.length > 0"
                     x-transition
                     style="position:absolute; top:100%; left:0; right:0; background:white; border:1px solid #DCE2E0; border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,0.08); z-index:50; margin-top:4px; overflow:hidden">
                    <template x-for="(item, index) in results" :key="index">
                        <div x-on:click="selectItem(item)"
                             x-on:mouseenter="highlighted = index"
                             :style="highlighted === index
                                 ? 'background:#F0FAF9; padding:8px 12px; cursor:pointer'
                                 : 'padding:8px 12px; cursor:pointer'"
                             style="border-bottom:1px solid #DCE2E0">
                            <div style="font-size:14px; color:#1B2027" x-text="item.label"></div>
                            <div style="font-size:11px; color:#5B6470" x-text="item.sub"></div>
                        </div>
                    </template>
                </div>
            </div>

            <select name="wilayah"
                    style="width:160px; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
                <option value="semua" {{ $wilayah === 'semua' ? 'selected' : '' }}>Semua Wilayah</option>
                @foreach($daftarWilayah as $w)
                    <option value="{{ $w }}" {{ $wilayah === $w ? 'selected' : '' }}>{{ $w }}</option>
                @endforeach
            </select>

            <select name="kabupaten"
                    style="width:160px; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
                <option value="semua" {{ $kabupaten === 'semua' ? 'selected' : '' }}>Semua Kabupaten</option>
                @foreach($daftarKabupaten as $k)
                    <option value="{{ $k }}" {{ $kabupaten === $k ? 'selected' : '' }}>{{ $k }}</option>
                @endforeach
            </select>

            <select name="status_lembaga"
                    style="width:160px; padding:8px 12px; border:1px solid #DCE2E0; border-radius:6px; font-family:Inter,sans-serif; font-size:14px; color:#1B2027; outline-color:#0E6E66">
                <option value="semua" {{ $status_lembaga === 'semua' ? 'selected' : '' }}>Semua Status</option>
                @foreach($daftarStatusLembaga as $s)
                    <option value="{{ $s }}" {{ $status_lembaga === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>

            <button type="submit"
                    style="padding:8px 20px; background:#0E6E66; color:white; border:none; border-radius:6px; font-size:14px; cursor:pointer; font-family:'IBM Plex Sans',sans-serif; font-weight:500">
                Cari
            </button>

            @if($search || $wilayah !== 'semua' || $kabupaten !== 'semua' || $status_lembaga !== 'semua')
                <a href="{{ route('pelanggan.index') }}"
                   style="padding:8px 16px; border:1px solid #DCE2E0; border-radius:6px; font-size:14px; color:#5B6470; font-family:Inter,sans-serif; text-decoration:none">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <p style="font-size:13px; color:#5B6470; margin-bottom:8px">
        @if($search || $wilayah !== 'semua' || $kabupaten !== 'semua' || $status_lembaga !== 'semua')
            Menampilkan {{ $pelanggan->total() }} dari {{ $totalSemua }} pelanggan
            @if($search) untuk "{{ $search }}"@endif
            @if($wilayah !== 'semua') · {{ $wilayah }}@endif
            @if($kabupaten !== 'semua') · {{ $kabupaten }}@endif
            @if($status_lembaga !== 'semua') · {{ $status_lembaga }}@endif
        @else
            {{ $totalSemua }} pelanggan
        @endif
    </p>

    @push('scripts')
    <script>
    function suggestPelanggan() {
        return {
            query: '{{ $search }}',
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
                        `{{ route('pelanggan.suggest') }}?q=${encodeURIComponent(this.query)}`
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
                this.$nextTick(() => this.$el.closest('form').submit());
            },
            highlightNext() {
                if (this.highlighted < this.results.length - 1)
                    this.highlighted++;
            },
            highlightPrev() {
                if (this.highlighted > 0) this.highlighted--;
            },
            selectHighlighted() {
                if (this.highlighted >= 0 && this.results[this.highlighted])
                    this.selectItem(this.results[this.highlighted]);
            },
            closeSuggest() {
                this.open = false;
                this.highlighted = -1;
            }
        }
    }
    </script>
    @endpush

    <div class="bg-surface border border-line rounded overflow-hidden">
        <div class="hidden sm:block overflow-x-auto">
            <table style="width:100%; min-width:950px; table-layout:fixed">
                <thead>
                    <tr class="border-b border-line">
                        <th style="width:16%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Nama</th>
                        <th style="width:8%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Kode</th>
                        <th style="width:18%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Lembaga</th>
                        <th style="width:10%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Kabupaten</th>
                        <th style="width:9%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Wilayah</th>
                        <th style="width:11%; padding:12px 16px; text-align:left; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Telepon</th>
                        <th style="width:12%; padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Batas Kredit</th>
                        <th style="width:8%; padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em">Tagihan Aktif</th>
                        <th style="width:8%; padding:12px 16px; text-align:right; font-size:11px; font-weight:500; color:#5B6470; text-transform:uppercase; letter-spacing:0.05em"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pelanggan as $p)
                        <tr class="border-b border-line hover:bg-paper transition">
                            <td style="padding:12px 16px; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0"
                                title="{{ $p->nama_pelanggan }}">
                                <a href="{{ route('pelanggan.show', $p) }}"
                                   style="color:#0E6E66; text-decoration:none; font-weight:500">
                                    {{ $p->nama_pelanggan }}
                                </a>
                            </td>
                            <td style="padding:12px 16px; font-size:12px; white-space:nowrap; font-family:'IBM Plex Mono',monospace; color:#1B2027">
                                {{ $p->kode_pelanggan ?: '-' }}
                            </td>
                            <td style="padding:12px 16px; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0"
                                title="{{ $p->nama_lembaga ?: $p->nama_pelanggan }}">
                                <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
                                    {{ $p->nama_lembaga ?: $p->nama_pelanggan }}
                                </div>
                                @if($p->status_lembaga)
                                    <div style="font-size:10px; color:#8A929C">
                                        {{ $p->status_lembaga }}
                                    </div>
                                @endif
                            </td>
                            <td style="padding:12px 16px; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0"
                                title="{{ $p->kabupaten ?: $p->wilayah }}">
                                {{ $p->kabupaten ?: $p->wilayah }}
                            </td>
                            <td style="padding:12px 16px; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:0"
                                title="{{ $p->wilayah }}">
                                {{ $p->wilayah ?: '-' }}
                            </td>
                            <td style="padding:12px 16px; font-size:14px; font-family:'IBM Plex Mono',monospace">
                                {{ $p->no_telepon ?: '-' }}
                            </td>
                            <td style="padding:12px 16px; font-size:14px; white-space:nowrap; text-align:right; font-family:'IBM Plex Mono',monospace">
                                Rp {{ number_format($p->batas_kredit, 2, ',', '.') }}
                            </td>
                            <td style="padding:12px 16px; font-size:14px; text-align:right; font-family:'IBM Plex Mono',monospace">
                                {{ $p->tagihan_aktif }}
                            </td>
                            <td style="padding:12px 16px; text-align:right; white-space:nowrap">
                                <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px">
                                    @can('update', $p)
                                        <a href="{{ route('pelanggan.edit', $p) }}"
                                           style="padding:2px 8px; border:1px solid #0E6E66; color:#0E6E66; background:white; border-radius:4px; font-size:11px; text-decoration:none; font-family:Inter,sans-serif; font-weight:500">
                                            Edit
                                        </a>
                                    @endcan
                                    @can('delete', $p)
                                        <form action="{{ route('pelanggan.destroy', $p) }}" method="POST" onsubmit="return confirm('Hapus pelanggan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    style="padding:2px 8px; border:1px solid #B33A2E; color:#B33A2E; background:white; border-radius:4px; font-size:11px; cursor:pointer; font-family:Inter,sans-serif; font-weight:500">
                                                Hapus
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding:32px; text-align:center; color:#5B6470; font-size:14px">
                                @if($search || $wilayah !== 'semua' || $kabupaten !== 'semua' || $status_lembaga !== 'semua')
                                    Tidak ada pelanggan yang cocok dengan pencarian ini.
                                    <a href="{{ route('pelanggan.index') }}" style="color:#0E6E66; text-decoration:none">Reset pencarian</a>
                                @else
                                    Belum ada pelanggan.
                                    @can('create', App\Models\Pelanggan::class)
                                        <a href="{{ route('pelanggan.create') }}" style="color:#0E6E66; text-decoration:none">Tambah pelanggan baru</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sm:hidden divide-y divide-line">
            @forelse ($pelanggan as $p)
                <div class="p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('pelanggan.show', $p) }}"
                           style="color:#0E6E66; text-decoration:none; font-weight:500; font-size:14px">
                            {{ $p->nama_pelanggan }}
                        </a>
                        <span style="font-size:12px; color:#5B6470">{{ $p->wilayah ?: '-' }}</span>
                    </div>
                    @if($p->kode_pelanggan || $p->nama_lembaga || $p->kabupaten)
                        <div style="font-size:12px; color:#5B6470">
                            <span style="font-family:'IBM Plex Mono',monospace">{{ $p->kode_pelanggan ?: '-' }}</span>
                            @if($p->nama_lembaga)
                                · {{ $p->nama_lembaga }}
                            @endif
                            @if($p->status_lembaga)
                                (@{{ $p->status_lembaga }})
                            @endif
                            · {{ $p->kabupaten ?: $p->wilayah }}
                        </div>
                    @endif
                    <div class="flex items-center justify-between text-sm">
                        <span style="color:#5B6470; font-family:'IBM Plex Mono',monospace">{{ $p->no_telepon ?: '-' }}</span>
                        <span style="font-family:'IBM Plex Mono',monospace; font-size:14px">Aktif: {{ $p->tagihan_aktif }}</span>
                    </div>
                    <div style="font-size:14px">
                        <span style="color:#5B6470">Batas kredit:</span>
                        <span style="font-family:'IBM Plex Mono',monospace; margin-left:4px">Rp {{ number_format($p->batas_kredit, 2, ',', '.') }}</span>
                    </div>
                    <div style="display:flex; gap:6px; padding-top:4px">
                        @can('update', $p)
                            <a href="{{ route('pelanggan.edit', $p) }}"
                               style="padding:2px 8px; border:1px solid #0E6E66; color:#0E6E66; background:white; border-radius:4px; font-size:11px; text-decoration:none; font-family:Inter,sans-serif; font-weight:500">
                                Edit
                            </a>
                        @endcan
                        @can('delete', $p)
                            <form action="{{ route('pelanggan.destroy', $p) }}" method="POST" onsubmit="return confirm('Hapus pelanggan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        style="padding:2px 8px; border:1px solid #B33A2E; color:#B33A2E; background:white; border-radius:4px; font-size:11px; cursor:pointer; font-family:Inter,sans-serif; font-weight:500">
                                    Hapus
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="p-8 text-center" style="color:#5B6470; font-size:14px">
                    @if($search || $wilayah !== 'semua' || $kabupaten !== 'semua' || $status_lembaga !== 'semua')
                        Tidak ada pelanggan yang cocok dengan pencarian ini.
                        <a href="{{ route('pelanggan.index') }}" style="color:#0E6E66; text-decoration:none">Reset pencarian</a>
                    @else
                        Belum ada pelanggan.
                        @can('create', App\Models\Pelanggan::class)
                            <a href="{{ route('pelanggan.create') }}" style="color:#0E6E66; text-decoration:none">Tambah pelanggan baru</a>
                        @endcan
                    @endif
                </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-line hidden sm:block">
            {{ $pelanggan->links() }}
        </div>
    </div>
</x-app-layout>
