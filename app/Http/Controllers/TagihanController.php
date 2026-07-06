<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagihanRequest;
use App\Http\Requests\UpdateTagihanRequest;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Tagihan;
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

        $tagihan = Tagihan::with('pelanggan')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('tagihan.index', compact('tagihan'));
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

    public function store(StoreTagihanRequest $request)
    {
        $validated = $request->validated();
        $pelanggan = Pelanggan::find($validated['id_pelanggan']);
        $tagihanBaru = (float) $validated['total_tagihan'];
        $warning = null;

        if ($pelanggan && $pelanggan->batas_kredit > 0) {
            $kredit = $pelanggan->cekBatasKredit($tagihanBaru);
            if ($kredit['exceeded']) {
                $warning = 'Total piutang '.e($pelanggan->nama_pelanggan).' melebihi batas kredit sebesar Rp '.number_format($kredit['kelebihan'], 0, ',', '.').'. Sisa limit: Rp '.number_format($kredit['sisa_limit'], 0, ',', '.').'.';
            }
        }

        Tagihan::create($validated);

        $redirect = redirect()->route('tagihan.index')->with('success', 'Tagihan berhasil dibuat.');
        if ($warning) {
            $redirect->with('warning', $warning);
        }

        return $redirect;
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
