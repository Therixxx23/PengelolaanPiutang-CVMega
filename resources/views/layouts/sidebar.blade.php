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

        @auth
            @if(Auth::user()->isAdministrasi())
                <x-nav-link :href="route('pelanggan.index')" :active="request()->routeIs('pelanggan.*')">
                    Pelanggan
                </x-nav-link>
                <x-nav-link :href="route('tagihan.index')" :active="request()->routeIs('tagihan.*')">
                    Tagihan
                </x-nav-link>
            @endif

            @if(Auth::user()->isPimpinan())
                @php $menungguApproval = \App\Models\Tagihan::menungguApproval()->count(); @endphp
                <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    Manajemen User
                </x-nav-link>
                <x-nav-link :href="route('approval.index')" :active="request()->routeIs('approval.*')">
                    <span class="flex items-center justify-between">
                        <span>Persetujuan</span>
                        @if($menungguApproval > 0)
                            <span class="inline-flex items-center bg-status-critical text-white text-[11px] px-1.5 py-0.5 rounded-full font-semibold">
                                {{ $menungguApproval }}
                            </span>
                        @endif
                    </span>
                </x-nav-link>
                <x-nav-link :href="route('log-aktivitas')" :active="request()->routeIs('log-aktivitas')">
                    Log Aktivitas
                </x-nav-link>
            @endif
        @endauth

        <div class="pt-4 mt-4 border-t border-line">
            <p class="px-3 text-xs font-medium text-ink-muted uppercase tracking-wider mb-2">Laporan</p>

            <x-nav-link :href="route('laporan.umur-piutang')" :active="request()->routeIs('laporan.umur-piutang')">
                Umur Piutang
            </x-nav-link>
            <x-nav-link :href="route('riwayat-pembayaran')" :active="request()->routeIs('riwayat-pembayaran')">
                Riwayat Pembayaran
            </x-nav-link>
            <x-nav-link :href="route('laporan.rekapitulasi')" :active="request()->routeIs('laporan.rekapitulasi')">
                Rekapitulasi
            </x-nav-link>
        </div>
    </nav>

    <div class="px-3 py-4 border-t border-line">
        <x-nav-link :href="route('profile.edit')" :active="false">
            {{ Auth::user()->name }}
        </x-nav-link>
        <p class="px-3 text-xs text-ink-muted mt-0.5">{{ auth()->user()->role_label }}</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-1">
            @csrf
            <button type="submit" class="w-full text-left px-3 py-2 text-sm text-ink-muted hover:text-ink transition">
                Log Out
            </button>
        </form>
    </div>
</aside>
