<?php

namespace App\Http\Controllers;

use App\Exports\RekapitulasiExport;
use App\Models\Pelanggan;
use App\Services\PiutangAgingService;
use App\Support\LikeQuery;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use OpenSpout\Writer\XLSX\Writer;

class LaporanRekapitulasiController extends Controller
{
    public function __invoke(Request $request, PiutangAgingService $agingService)
    {
        $this->authorize('viewLaporan');

        $search = $request->get('search', '');
        $wilayah = $request->get('wilayah', 'semua');
        $kabupaten = $request->get('kabupaten', 'semua');
        $totalSemua = Pelanggan::count();

        $semuaPelanggan = Pelanggan::with(['tagihan' => fn ($q) => $q->aktif(), 'tagihan.pembayaran'])
            ->when($search, fn ($q) => $q->where('nama_pelanggan', 'like', '%'.LikeQuery::escape($search).'%'))
            ->when($wilayah !== 'semua', fn ($q) => $q->where('wilayah', $wilayah))
            ->when($kabupaten !== 'semua', fn ($q) => $q->where('kabupaten', $kabupaten))
            ->orderBy('nama_pelanggan')
            ->get();

        $ringkasan = $semuaPelanggan->map(function ($pelanggan) use ($agingService) {
            $totalTagihan = $pelanggan->tagihan->sum('total_tagihan');
            $totalTerbayar = $pelanggan->tagihan->flatMap->pembayaran->sum('jumlah_bayar');
            $sisa = $totalTagihan - $totalTerbayar;

            $aktif = $pelanggan->tagihan->where('status', 'belum_lunas');
            $bucketTerburuk = null;

            foreach ($aktif as $t) {
                $b = $agingService->getBucketForTagihan($t);
                $order = ['lancar' => 0, '0-30' => 1, '31-60' => 2, '>60' => 3];
                if ($bucketTerburuk === null || ($order[$b] ?? 0) > ($order[$bucketTerburuk] ?? 0)) {
                    $bucketTerburuk = $b;
                }
            }

            return (object) [
                'pelanggan' => $pelanggan,
                'total_tagihan' => $totalTagihan,
                'total_terbayar' => $totalTerbayar,
                'sisa_piutang' => $sisa,
                'bucket_terburuk' => $bucketTerburuk,
                'jumlah_tagihan' => $pelanggan->tagihan->count(),
                'jumlah_tagihan_aktif' => $aktif->count(),
            ];
        })->sortByDesc('sisa_piutang')->values();

        $totalPiutang = $ringkasan->sum('total_tagihan');
        $totalTertagih = $ringkasan->sum('total_terbayar');
        $totalSisa = $ringkasan->sum('sisa_piutang');
        $totalPelanggan = $ringkasan->count();

        $top10 = $ringkasan->take(10);
        $chartLabels = $top10->pluck('pelanggan.nama_pelanggan')->map(fn ($n) => explode(' ', $n)[0])->toArray();
        $chartData = $top10->pluck('sisa_piutang')->toArray();

        $perPage = 10;
        $currentPage = Paginator::resolveCurrentPage();
        $paginated = new LengthAwarePaginator(
            $ringkasan->forPage($currentPage, $perPage)->values(),
            $totalPelanggan,
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()],
        );
        $paginated->appends([
            'search' => $search,
            'wilayah' => $wilayah,
            'kabupaten' => $kabupaten,
        ]);

        $daftarWilayah = Pelanggan::distinct()->pluck('wilayah')->sort();
        $daftarKabupaten = Pelanggan::whereNotNull('kabupaten')->distinct()->orderBy('kabupaten')->pluck('kabupaten');

        return view('laporan.rekapitulasi', compact(
            'paginated', 'totalPiutang', 'totalTertagih', 'totalSisa', 'totalPelanggan',
            'totalSemua', 'chartLabels', 'chartData', 'search', 'wilayah', 'kabupaten',
            'daftarWilayah', 'daftarKabupaten',
        ));
    }

    public function exportExcel(Request $request)
    {
        $this->authorize('viewLaporan');

        $search = $request->get('search', '');
        $wilayah = $request->get('wilayah', 'semua');
        $kabupaten = $request->get('kabupaten', 'semua');

        $filename = 'Rekapitulasi-Piutang';
        if ($search) {
            $filename .= '-'.preg_replace('/[^a-zA-Z0-9]/', '-', $search);
        }
        if ($wilayah !== 'semua') {
            $filename .= '-'.str_replace('/', '-', $wilayah);
        }
        if ($kabupaten !== 'semua') {
            $filename .= '-'.str_replace('/', '-', $kabupaten);
        }
        $filename .= '-'.now()->format('Y-m-d').'.xlsx';

        $path = tempnam(sys_get_temp_dir(), 'piutang').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);

        $export = new RekapitulasiExport($search, $wilayah, $kabupaten);
        $export->write($writer);

        $writer->close();

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
