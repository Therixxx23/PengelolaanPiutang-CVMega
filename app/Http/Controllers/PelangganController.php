<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePelangganRequest;
use App\Http\Requests\UpdatePelangganRequest;
use App\Models\Pelanggan;
use Illuminate\Http\Request;

class PelangganController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Pelanggan::class);

        $search = request('search', '');
        $wilayah = request('wilayah', 'semua');
        $totalSemua = Pelanggan::count();

        $pelanggan = Pelanggan::withCount(['tagihan as tagihan_aktif' => fn ($q) => $q->where('status', 'belum_lunas')])
            ->when($search, fn ($q) => $q
                ->where('nama_pelanggan', 'like', "%{$search}%")
                ->orWhere('wilayah', 'like', "%{$search}%")
            )
            ->when($wilayah !== 'semua', fn ($q) => $q->where('wilayah', $wilayah))
            ->orderBy('nama_pelanggan')
            ->paginate(10);

        $pelanggan->appends(['search' => $search, 'wilayah' => $wilayah]);

        $daftarWilayah = Pelanggan::distinct()->pluck('wilayah')->sort();

        return view('pelanggan.index', compact('pelanggan', 'search', 'wilayah', 'daftarWilayah', 'totalSemua'));
    }

    public function suggest(Request $request)
    {
        $q = $request->get('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = Pelanggan::where('nama_pelanggan', 'like', "%{$q}%")
            ->orWhere('wilayah', 'like', "%{$q}%")
            ->limit(8)
            ->get(['nama_pelanggan', 'wilayah'])
            ->map(fn ($p) => [
                'type' => 'pelanggan',
                'label' => $p->nama_pelanggan,
                'sub' => $p->wilayah,
            ]);

        return response()->json($results->values());
    }

    public function create()
    {
        $this->authorize('create', Pelanggan::class);

        return view('pelanggan.create');
    }

    public function store(StorePelangganRequest $request)
    {
        Pelanggan::create($request->validated());

        return redirect()->route('pelanggan.index')
            ->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function show(Pelanggan $pelanggan)
    {
        $this->authorize('view', $pelanggan);

        $pelanggan->load('tagihan.pembayaran');

        return view('pelanggan.show', compact('pelanggan'));
    }

    public function edit(Pelanggan $pelanggan)
    {
        $this->authorize('update', $pelanggan);

        return view('pelanggan.edit', compact('pelanggan'));
    }

    public function update(UpdatePelangganRequest $request, Pelanggan $pelanggan)
    {
        $pelanggan->update($request->validated());

        return redirect()->route('pelanggan.index')
            ->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Pelanggan $pelanggan)
    {
        $this->authorize('delete', $pelanggan);
        $pelanggan->delete();

        return redirect()->route('pelanggan.index')
            ->with('success', 'Pelanggan berhasil dihapus.');
    }
}
