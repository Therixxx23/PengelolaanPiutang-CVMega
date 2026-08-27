<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\PiutangAgingService;

class DashboardController extends Controller
{
    public function index(PiutangAgingService $agingService)
    {
        $user = auth()->user();

        // Data umum untuk semua role
        $data = [
            'totalBelumLunas' => Tagihan::aktif()
                ->where('status', 'belum_lunas')->count(),
            'jatuhTempoMingguIni' => Tagihan::aktif()
                ->where('status', 'belum_lunas')
                ->whereBetween('tanggal_jatuh_tempo', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ])->count(),
            'totalPiutang' => Tagihan::aktif()
                ->where('status', 'belum_lunas')
                ->sum('total_tagihan'),
            'totalPelangganAktif' => Pelanggan::whereHas('tagihan',
                fn ($q) => $q->where('status', 'belum_lunas')
                    ->where('approval_status', 'aktif'))->count(),
        ];

        // Data khusus Pimpinan
        if ($user->isPimpinan()) {
            $data['menungguApproval'] = Tagihan::menungguApproval()
                ->with('pelanggan')
                ->latest()->limit(5)->get();

            $data['totalMenunggu'] = Tagihan::menungguApproval()
                ->count();

            $data['disetujuiHariIni'] = Tagihan::where('approval_status', 'aktif')
                ->whereDate('approved_at', today())
                ->count();

            $data['ditolakHariIni'] = Tagihan::where('approval_status', 'ditolak')
                ->whereDate('approved_at', today())
                ->count();

            $data['nilaiMenunggu'] = Tagihan::menungguApproval()
                ->sum('total_tagihan');

            // Top 5 pelanggan dengan piutang terbesar
            $data['topPiutang'] = Pelanggan::withSum([
                'tagihan as total_piutang' => fn ($q) => $q->where('status', 'belum_lunas')
                    ->where('approval_status', 'aktif'),
            ], 'total_tagihan')
                ->orderByDesc('total_piutang')
                ->limit(5)->get();

            // Aging summary
            $data['agingSummary'] = $agingService->getBucketSummary();

            // Monitoring Tim Sales
            $data['monitoringSales'] = User::where('role', 'sales')
                ->where('is_active', true)
                ->withCount([
                    'assignedTagihan as total_assigned' => fn ($q) => $q->where('approval_status', 'aktif')
                        ->where('status', 'belum_lunas'),
                    'assignedTagihan as belum_ditagih' => fn ($q) => $q->where('approval_status', 'aktif')
                        ->where('status', 'belum_lunas')
                        ->where('status_penagihan', 'belum_ditagih'),
                    'assignedTagihan as sedang_proses' => fn ($q) => $q->where('approval_status', 'aktif')
                        ->where('status', 'belum_lunas')
                        ->whereIn('status_penagihan', ['sedang_ditagih', 'janji_bayar']),
                    'assignedTagihan as sudah_ditagih' => fn ($q) => $q->where('approval_status', 'aktif')
                        ->where('status', 'belum_lunas')
                        ->where('status_penagihan', 'sudah_ditagih'),
                ])->get();
        }

        // Data khusus Bagian Administrasi
        if ($user->isAdministrasi()) {
            $data['tagihanTerbaru'] = Tagihan::with('pelanggan')
                ->latest()->limit(5)->get();

            $data['jatuhTempoList'] = Tagihan::aktif()
                ->with('pelanggan')
                ->where('status', 'belum_lunas')
                ->whereBetween('tanggal_jatuh_tempo', [
                    now(), now()->addDays(7),
                ])
                ->orderBy('tanggal_jatuh_tempo')
                ->limit(5)->get();

            $data['tagihanDitolak'] = Tagihan::where('approval_status', 'ditolak')
                ->with('pelanggan')
                ->latest('approved_at')
                ->limit(3)->get();

            $data['agingSummary'] = $agingService->getBucketSummary();
        }

        // Data khusus Bagian Keuangan
        if ($user->isKeuangan()) {
            $data['tagihanTerbaru'] = Tagihan::aktif()
                ->with('pelanggan')
                ->latest()->limit(5)->get();
            $data['agingSummary'] = $agingService->getBucketSummary();
            $data['topPiutang'] = Pelanggan::withSum([
                'tagihan as total_piutang' => fn ($q) => $q->where('status', 'belum_lunas')
                    ->where('approval_status', 'aktif'),
            ], 'total_tagihan')
                ->orderByDesc('total_piutang')
                ->limit(5)->get();
        }

        // Data khusus Sales
        if ($user->isSales()) {
            $data['tagihanAssigned'] = Tagihan::aktif()
                ->where('assigned_sales_id', $user->id)
                ->with('pelanggan')
                ->orderBy('tanggal_jatuh_tempo')
                ->paginate(10);

            $data['ringkasanStatus'] = [
                'belum_ditagih' => Tagihan::aktif()
                    ->where('assigned_sales_id', $user->id)
                    ->where('status_penagihan', 'belum_ditagih')->count(),
                'sedang_ditagih' => Tagihan::aktif()
                    ->where('assigned_sales_id', $user->id)
                    ->where('status_penagihan', 'sedang_ditagih')->count(),
                'janji_bayar' => Tagihan::aktif()
                    ->where('assigned_sales_id', $user->id)
                    ->where('status_penagihan', 'janji_bayar')->count(),
                'sudah_ditagih' => Tagihan::aktif()
                    ->where('assigned_sales_id', $user->id)
                    ->where('status_penagihan', 'sudah_ditagih')->count(),
            ];

            $data['totalAssigned'] = array_sum($data['ringkasanStatus']);
        }

        return view('dashboard', $data);
    }
}
