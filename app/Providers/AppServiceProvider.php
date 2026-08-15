<?php

namespace App\Providers;

use App\Models\LogAktivitas;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\User;
use App\Policies\LogAktivitasPolicy;
use App\Policies\PelangganPolicy;
use App\Policies\PembayaranPolicy;
use App\Policies\TagihanPolicy;
use App\Services\ApprovalService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ApprovalService::class);
    }

    public function boot(): void
    {
        Gate::policy(Pelanggan::class, PelangganPolicy::class);
        Gate::policy(Tagihan::class, TagihanPolicy::class);
        Gate::policy(Pembayaran::class, PembayaranPolicy::class);
        Gate::policy(LogAktivitas::class, LogAktivitasPolicy::class);

        Gate::define('viewLaporan', function (User $user) {
            return $user->isAdministrasi() || $user->canViewReports();
        });

        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by((string) $request->input('email')),
                Limit::perMinute(10)->by((string) $request->ip()),
            ];
        });
    }
}
