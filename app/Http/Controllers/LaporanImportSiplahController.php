<?php

namespace App\Http\Controllers;

use App\Exports\LaporanSipLahExport;
use App\Models\Pelanggan;
use App\Models\Tagihan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use OpenSpout\Writer\XLSX\Writer;

class LaporanImportSiplahController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('viewLaporan');

        $periode = $request->get('periode', date('Y-m'));
        $wilayah = $request->get('wilayah', 'semua');
        $sumber = $request->get('sumber_dana', 'semua');
        $sales = $request->get('sales', 'semua');

        $query = $this->baseQuery($periode, $wilayah, $sumber, $sales);

        $allData = (clone $query)->get();
        $summary = [
            'total_faktur' => $allData->count(),
            'total_nilai' => $allData->sum('total_tagihan'),
            'total_item' => $allData->sum(fn ($t) => $t->items->count()),
            'total_qty' => $allData->sum(fn ($t) => $t->items->sum('qty_netto')),
            'sudah_lunas' => $allData->where('status', 'lunas')->count(),
            'belum_lunas' => $allData->where('status', 'belum_lunas')->count(),
        ];

        $perSumberDana = $allData->groupBy('sumber_dana')
            ->map(fn ($g) => [
                'jumlah_faktur' => $g->count(),
                'total_nilai' => $g->sum('total_tagihan'),
            ]);

        $perSales = $allData->groupBy('nama_sales')
            ->map(fn ($g) => [
                'jumlah_faktur' => $g->count(),
                'total_nilai' => $g->sum('total_tagihan'),
            ])->sortByDesc('total_nilai');

        $daftarPeriode = Tagihan::whereHas('items')
            ->where('approval_status', 'aktif')
            ->pluck('tanggal_tagihan')
            ->map(fn ($d) => \Illuminate\Support\Carbon::parse((string) $d)->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values();

        $daftarWilayah = Pelanggan::whereHas('tagihan', fn ($q) => $q->whereHas('items'))
            ->distinct()
            ->pluck('kabupaten')
            ->filter()
            ->sort()
            ->values();

        $daftarSumber = Tagihan::whereHas('items')->distinct()->pluck('sumber_dana')->filter()
            ->sort()->values();

        $daftarSales = Tagihan::whereHas('items')->distinct()->pluck('nama_sales')->filter()
            ->sort()->values();

        $tagihan = $query->latest('tanggal_tagihan')->paginate(15);
        $tagihan->appends($request->only(['periode', 'wilayah', 'sumber_dana', 'sales']));

        return view('laporan.import-siplah', compact(
            'tagihan', 'summary', 'perSumberDana', 'perSales',
            'daftarPeriode', 'daftarWilayah', 'daftarSumber',
            'daftarSales', 'periode', 'wilayah', 'sumber', 'sales',
        ));
    }

    public function exportExcel(Request $request)
    {
        $this->authorize('viewLaporan');

        $periode = $request->get('periode', date('Y-m'));
        $wilayah = $request->get('wilayah', 'semua');
        $sumber = $request->get('sumber_dana', 'semua');
        $sales = $request->get('sales', 'semua');

        $query = $this->baseQuery($periode, $wilayah, $sumber, $sales);
        $tagihan = $query->latest('tanggal_tagihan')->get();

        $filename = 'Laporan-SIPLAH-'.$periode.'-'.now()->format('Y-m-d').'.xlsx';

        $path = tempnam(sys_get_temp_dir(), 'siplah').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);

        $export = new LaporanSipLahExport($tagihan);
        $export->write($writer);

        $writer->close();

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    protected function baseQuery(string $periode, string $wilayah, string $sumber, string $sales)
    {
        $query = Tagihan::with(['pelanggan', 'items'])
            ->whereHas('items')
            ->where('approval_status', 'aktif');

        if ($wilayah !== 'semua') {
            $query->whereHas('pelanggan', fn ($p) => $p->where('kabupaten', $wilayah));
        }

        if ($sumber !== 'semua') {
            $query->where('sumber_dana', $sumber);
        }

        if ($sales !== 'semua') {
            $query->where('nama_sales', $sales);
        }

        if ($periode !== 'semua' && preg_match('/^\d{4}-\d{2}$/', $periode)) {
            $start = Carbon::createFromFormat('Y-m', $periode)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $query->whereBetween('tanggal_tagihan', [$start, $end]);
        }

        return $query;
    }
}
