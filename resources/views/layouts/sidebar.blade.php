<aside class="w-60 bg-surface border-r border-line flex flex-col shrink-0">
    <div class="px-6 py-6 border-b border-line">
        <a href="{{ route('dashboard') }}" class="font-display text-lg font-semibold text-ink">
            Sistem Piutang
        </a>
        <p class="text-xs text-ink-muted mt-1">CV. Mega Setia Abadi</p>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1">
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            Dashboard
        </x-nav-link>

        @can('viewAny', App\Models\Pelanggan::class)
            <x-nav-link :href="route('pelanggan.index')" :active="request()->routeIs('pelanggan.*')">
                Pelanggan
            </x-nav-link>
        @endcan

        {{-- Tagihan & Pembayaran routes will be added in next steps --}}

        <div class="pt-4 mt-4 border-t border-line">
            <p class="px-3 text-xs font-medium text-ink-muted uppercase tracking-wider mb-2">Laporan</p>

            <x-nav-link :href="#" :active="false">
                Umur Piutang
            </x-nav-link>
            <x-nav-link :href="#" :active="false">
                Riwayat Pembayaran
            </x-nav-link>
            <x-nav-link :href="#" :active="false">
                Rekapitulasi
            </x-nav-link>
        </div>
    </nav>

    <div class="px-3 py-4 border-t border-line">
        <x-nav-link :href="route('profile.edit')" :active="false">
            {{ Auth::user()->name }}
        </x-nav-link>
        <form method="POST" action="{{ route('logout') }}" class="mt-1">
            @csrf
            <button type="submit" class="w-full text-left px-3 py-2 text-sm text-ink-muted hover:text-ink transition">
                Log Out
            </button>
        </form>
    </div>
</aside>
