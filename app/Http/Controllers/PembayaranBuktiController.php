<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePembayaranBuktiRequest;
use App\Models\PembayaranBukti;
use App\Models\Tagihan;
use App\Services\PembayaranBuktiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PembayaranBuktiController extends Controller
{
    public function __construct(
        protected PembayaranBuktiService $service,
    ) {}

    public function index()
    {
        $this->authorize('viewAny', PembayaranBukti::class);

        $user = auth()->user();
        $filter = request('filter', 'semua');

        $bukti = PembayaranBukti::query()
            ->with(['tagihan.pelanggan', 'sales', 'validator'])
            ->when($user->isSales(), fn ($q) => $q->where('sales_id', $user->id))
            ->when($filter !== 'semua', fn ($q) => $q->where('status', $filter))
            ->latest()
            ->paginate(15);

        $bukti->appends(['filter' => $filter]);

        return view('pembayaran-bukti.index', compact('bukti', 'filter'));
    }

    public function create()
    {
        $this->authorize('create', PembayaranBukti::class);

        $tagihanBelumLunas = Tagihan::aktif()
            ->bisaDibayar()
            ->where('assigned_sales_id', auth()->id())
            ->with(['pelanggan', 'pembayaran'])
            ->orderBy('tanggal_jatuh_tempo')
            ->get();

        return view('pembayaran-bukti.create', compact('tagihanBelumLunas'));
    }

    public function store(StorePembayaranBuktiRequest $request)
    {
        $this->authorize('create', PembayaranBukti::class);

        $tagihan = Tagihan::findOrFail($request->tagihan_id);

        try {
            $this->service->simpanBukti(
                $tagihan,
                auth()->user(),
                $request->validated()
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('pembayaran-bukti.index')
            ->with('success', 'Bukti pembayaran berhasil diunggah dan menunggu validasi Bagian Keuangan.');
    }

    public function setujui(Request $request, PembayaranBukti $bukti)
    {
        $this->authorize('approve', $bukti);

        try {
            $this->service->setujuiBukti($bukti, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bukti pembayaran disetujui dan pembayaran berhasil dicatat.');
    }

    public function tolak(Request $request, PembayaranBukti $bukti)
    {
        $this->authorize('reject', $bukti);

        $request->validate([
            'catatan_reject' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'catatan_reject.required' => 'Catatan penolakan wajib diisi.',
            'catatan_reject.min' => 'Catatan penolakan minimal 5 karakter.',
        ]);

        try {
            $this->service->tolakBukti($bukti, $request->user(), $request->catatan_reject);
        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bukti pembayaran ditolak.');
    }

    public function download(PembayaranBukti $bukti)
    {
        $this->authorize('view', $bukti);

        if (! $bukti->file_path || ! Storage::disk('local')->exists($bukti->file_path)) {
            abort(404, 'File bukti tidak ditemukan.');
        }

        $noInvoice = str_replace(['/', '\\'], '-', (string) $bukti->tagihan->no_invoice);
        $namaFile = 'bukti-bayar-'.$bukti->id.'-'.$noInvoice.'.'.pathinfo($bukti->file_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($bukti->file_path, $namaFile);
    }
}
