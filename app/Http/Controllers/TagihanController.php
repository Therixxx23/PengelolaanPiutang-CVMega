<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagihanRequest;
use App\Http\Requests\UpdateTagihanRequest;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\InvoiceNumberService;
use App\Services\PembayaranService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TagihanController extends Controller
{
    public function index()
    {
        $tagihan = Tagihan::with('pelanggan')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('tagihan.index', compact('tagihan'));
    }

    public function create(InvoiceNumberService $invoiceService)
    {
        $pelanggan = Pelanggan::orderBy('nama_pelanggan')->get();
        $noInvoice = $invoiceService->generate();

        return view('tagihan.create', compact('pelanggan', 'noInvoice'));
    }

    public function store(StoreTagihanRequest $request)
    {
        Tagihan::create($request->validated());

        return redirect()->route('tagihan.index')
            ->with('success', 'Tagihan berhasil dibuat.');
    }

    public function show(Tagihan $tagihan)
    {
        $tagihan->load(['pelanggan', 'pembayaran']);

        return view('tagihan.show', compact('tagihan'));
    }

    public function edit(Tagihan $tagihan)
    {
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
}
