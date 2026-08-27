<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePembayaranRequest;
use App\Http\Requests\StoreTagihanRequest;
use App\Http\Requests\UpdateTagihanRequest;
use App\Models\LogAktivitas;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\ApprovalService;
use App\Services\InvoiceNumberService;
use App\Services\PembayaranService;
use App\Services\PenagihanService;
use App\Support\LikeQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TagihanController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Tagihan::class);

        $search = request('search', '');
        $status = request('status', 'semua');
        $totalSemua = Tagihan::count();

        $tagihan = Tagihan::with('pelanggan')
            ->when($search, function ($q) use ($search) {
                $like = '%'.LikeQuery::escape($search).'%';

                $q->where('no_invoice', 'like', $like)
                    ->orWhereHas('pelanggan', function ($q) use ($like) {
                        $q->where('nama_pelanggan', 'like', $like);
                    });
            })
            ->when($status !== 'semua', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->latest('tanggal_tagihan')
            ->paginate(10);

        $tagihan->appends(['search' => $search, 'status' => $status]);

        return view('tagihan.index', compact('tagihan', 'search', 'status', 'totalSemua'));
    }

    public function suggest(Request $request)
    {
        $q = $request->get('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $like = '%'.LikeQuery::escape($q).'%';

        $invoices = Tagihan::where('no_invoice', 'like', $like)
            ->limit(5)
            ->pluck('no_invoice')
            ->map(fn ($v) => ['type' => 'invoice', 'label' => $v]);

        $pelanggan = Pelanggan::where('nama_pelanggan', 'like', $like)
            ->limit(5)
            ->pluck('nama_pelanggan')
            ->map(fn ($v) => ['type' => 'pelanggan', 'label' => $v]);

        return response()->json(
            $invoices->merge($pelanggan)->values()->take(8)
        );
    }

    public function create(InvoiceNumberService $invoiceService)
    {
        $this->authorize('create', Tagihan::class);

        $pelanggan = Pelanggan::orderBy('nama_pelanggan')->get()
            ->map(fn ($p) => (object) [
                'id_pelanggan' => $p->id_pelanggan,
                'nama_pelanggan' => $p->nama_pelanggan,
                'wilayah' => $p->wilayah,
                'batas_kredit' => $p->batas_kredit,
                'total_piutang_aktif' => $p->totalPiutangAktif(),
                'sisa_limit' => $p->cekBatasKredit('0')['sisa_limit'],
            ]);
        $noInvoice = $invoiceService->generate();

        return view('tagihan.create', compact('pelanggan', 'noInvoice'));
    }

    public function store(StoreTagihanRequest $request, ApprovalService $approvalService)
    {
        $this->authorize('create', Tagihan::class);

        $tagihan = new Tagihan($request->validated());
        $tagihan->id_pelanggan = $request->id_pelanggan;
        $tagihan->no_invoice = $this->generateNomorInvoice();
        $tagihan->status = 'belum_lunas';

        // Load relasi pelanggan dulu supaya accessor
        // butuh_approval bisa hitung batas_kredit.
        $tagihan->setRelation(
            'pelanggan',
            Pelanggan::find($request->id_pelanggan)
        );

        // Tentukan approval_status otomatis (mulai sebagai aktif).
        $tagihan->approval_status = 'aktif';
        $tagihan->approval_status = $approvalService->tentukanStatus($tagihan);

        $tagihan->save();

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aksi' => 'buat_tagihan',
            'model_type' => class_basename($tagihan),
            'model_id' => $tagihan->id_tagihan,
            'data_sebelum' => null,
            'data_sesudah' => [
                'no_invoice' => $tagihan->no_invoice,
                'total_tagihan' => $tagihan->total_tagihan,
                'approval_status' => $tagihan->approval_status,
            ],
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        // Pesan berbeda tergantung status
        if ($tagihan->approval_status === 'menunggu_persetujuan') {
            return redirect()
                ->route('tagihan.show', $tagihan)
                ->with('warning', 'Tagihan berhasil dibuat namun memerlukan persetujuan Pimpinan karena melebihi threshold. Tagihan belum aktif dan belum bisa menerima pembayaran.');
        }

        return redirect()
            ->route('tagihan.show', $tagihan)
            ->with('success', 'Tagihan berhasil dibuat dan langsung aktif.');
    }

    private function generateNomorInvoice(): string
    {
        return app(InvoiceNumberService::class)->generate();
    }

    public function show(Tagihan $tagihan)
    {
        $this->authorize('view', $tagihan);

        $tagihan->load(['pelanggan', 'pembayaran', 'items', 'assignedSales', 'catatanPenagihan.user']);

        return view('tagihan.show', compact('tagihan'));
    }

    public function edit(Tagihan $tagihan)
    {
        $this->authorize('update', $tagihan);

        $pelanggan = Pelanggan::orderBy('nama_pelanggan')->get();

        return view('tagihan.edit', compact('tagihan', 'pelanggan'));
    }

    public function update(UpdateTagihanRequest $request, Tagihan $tagihan, PembayaranService $pembayaranService, ApprovalService $approvalService)
    {
        $this->authorize('update', $tagihan);

        $tagihan->update($request->validated());

        $pembayaranService->sinkronkanStatus($tagihan);
        $tagihan->approval_status = $approvalService->tentukanStatus($tagihan);
        $tagihan->save();

        if ($tagihan->approval_status === 'menunggu_persetujuan') {
            return redirect()->route('tagihan.index')
                ->with('warning', 'Tagihan berhasil diperbarui namun kini memerlukan persetujuan Pimpinan karena melebihi batas kredit atau threshold.');
        }

        return redirect()->route('tagihan.index')
            ->with('success', 'Tagihan berhasil diperbarui.');
    }

    public function destroy(Tagihan $tagihan)
    {
        $this->authorize('delete', $tagihan);
        $tagihan->delete();

        return redirect()->route('tagihan.index')
            ->with('success', 'Tagihan berhasil dihapus.');
    }

    public function bayar(StorePembayaranRequest $request, Tagihan $tagihan, PembayaranService $pembayaranService)
    {
        $this->authorize('create', Pembayaran::class);

        try {
            $pembayaranService->catatPembayaran($tagihan, $request->validated());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('tagihan.show', $tagihan)
            ->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function exportPdf(Tagihan $tagihan)
    {
        $this->authorize('update', $tagihan);

        $tagihan->load(['pelanggan', 'pembayaran']);

        $pdf = Pdf::loadView('pdf.surat_tagihan', compact('tagihan'));

        $filename = 'Surat-Tagihan-'.str_replace('/', '-', $tagihan->no_invoice).'.pdf';

        return $pdf->download($filename);
    }

    public function showSales(Tagihan $tagihan)
    {
        $this->authorize('viewSales', $tagihan);

        $tagihan->load(['pelanggan', 'items', 'catatanPenagihan.user']);

        return view('tagihan.show-sales', compact('tagihan'));
    }

    public function updateStatus(
        Request $request,
        Tagihan $tagihan,
        PenagihanService $service
    ) {
        $this->authorize('updatePenagihan', $tagihan);

        $request->validate([
            'status_penagihan' => 'required|in:belum_ditagih,sedang_ditagih,'
                .'janji_bayar,sudah_ditagih',
            'catatan' => 'nullable|string|max:500',
        ], [
            'status_penagihan.required' => 'Status wajib dipilih.',
            'status_penagihan.in' => 'Status tidak valid.',
        ]);

        $service->updateStatus(
            $tagihan,
            auth()->user(),
            $request->status_penagihan,
            $request->catatan
        );

        return back()->with('success',
            'Status penagihan diperbarui: '.$tagihan->fresh()->status_penagihan_label
        );
    }

    public function assignSales(
        Request $request,
        Tagihan $tagihan,
        PenagihanService $service
    ) {
        $this->authorize('update', $tagihan);

        $request->validate([
            'sales_id' => 'nullable|exists:users,id',
        ]);

        $service->assignSales($tagihan, $request->sales_id);

        return back()->with('success', 'Sales berhasil di-assign.');
    }
}
