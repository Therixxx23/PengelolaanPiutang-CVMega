<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagihanRequest;
use App\Http\Requests\UpdateTagihanRequest;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\ApprovalService;
use App\Services\InvoiceNumberService;
use App\Services\PembayaranService;
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
                $q->where('no_invoice', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($q) use ($search) {
                        $q->where('nama_pelanggan', 'like', "%{$search}%");
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

        $invoices = Tagihan::where('no_invoice', 'like', "%{$q}%")
            ->limit(5)
            ->pluck('no_invoice')
            ->map(fn ($v) => ['type' => 'invoice', 'label' => $v]);

        $pelanggan = Pelanggan::where('nama_pelanggan', 'like', "%{$q}%")
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
                'sisa_limit' => max(0, (float) $p->batas_kredit - $p->totalPiutangAktif()),
            ]);
        $noInvoice = $invoiceService->generate();

        return view('tagihan.create', compact('pelanggan', 'noInvoice'));
    }

    public function store(StoreTagihanRequest $request, ApprovalService $approvalService)
    {
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

        $tagihan->load(['pelanggan', 'pembayaran']);

        return view('tagihan.show', compact('tagihan'));
    }

    public function edit(Tagihan $tagihan)
    {
        $this->authorize('update', $tagihan);

        $pelanggan = Pelanggan::orderBy('nama_pelanggan')->get();

        return view('tagihan.edit', compact('tagihan', 'pelanggan'));
    }

    public function update(UpdateTagihanRequest $request, Tagihan $tagihan)
    {
        $tagihan->update($request->validated());

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

    public function bayar(Request $request, Tagihan $tagihan, PembayaranService $pembayaranService)
    {
        $this->authorize('create', Pembayaran::class);

        $validated = $request->validate([
            'tanggal_bayar' => ['required', 'date'],
            'jumlah_bayar' => ['required', 'numeric', 'min:0.01'],
            'metode_bayar' => ['required', 'string', 'max:30'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $pembayaranService->catatPembayaran($tagihan, $validated);
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
}
