<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePelangganRequest;
use App\Http\Requests\UpdatePelangganRequest;
use App\Models\Pelanggan;
use App\Support\LikeQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PelangganController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Pelanggan::class);

        $search = request('search', '');
        $wilayah = request('wilayah', 'semua');
        $kabupaten = request('kabupaten', 'semua');
        $status_lembaga = request('status_lembaga', 'semua');
        $totalSemua = Pelanggan::count();

        $like = '%'.LikeQuery::escape($search).'%';

        $pelanggan = Pelanggan::withCount(['tagihan as tagihan_aktif' => fn ($q) => $q->where('status', 'belum_lunas')])
            ->when($search, fn ($q) => $q
                ->where('nama_pelanggan', 'like', $like)
                ->orWhere('wilayah', 'like', $like)
            )
            ->when($wilayah !== 'semua', fn ($q) => $q->where('wilayah', $wilayah))
            ->when($kabupaten !== 'semua', fn ($q) => $q->where('kabupaten', $kabupaten))
            ->when($status_lembaga !== 'semua', fn ($q) => $q->where('status_lembaga', $status_lembaga))
            ->orderBy('nama_pelanggan')
            ->paginate(10);

        $pelanggan->appends([
            'search' => $search,
            'wilayah' => $wilayah,
            'kabupaten' => $kabupaten,
            'status_lembaga' => $status_lembaga,
        ]);

        $daftarWilayah = Pelanggan::distinct()->pluck('wilayah')->sort();
        $daftarKabupaten = Pelanggan::whereNotNull('kabupaten')->distinct()->orderBy('kabupaten')->pluck('kabupaten');
        $daftarStatusLembaga = ['NEGERI', 'SWASTA'];

        return view('pelanggan.index', compact(
            'pelanggan', 'search', 'wilayah', 'kabupaten', 'status_lembaga',
            'daftarWilayah', 'daftarKabupaten', 'daftarStatusLembaga', 'totalSemua'
        ));
    }

    public function suggest(Request $request)
    {
        $q = $request->get('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $like = '%'.LikeQuery::escape($q).'%';

        $results = Pelanggan::where('nama_pelanggan', 'like', $like)
            ->orWhere('wilayah', 'like', $like)
            ->limit(8)
            ->get(['nama_pelanggan', 'wilayah'])
            ->map(fn ($p) => [
                'type' => 'pelanggan',
                'label' => $p->nama_pelanggan,
                'sub' => $p->wilayah,
            ]);

        return response()->json($results->values());
    }

    public function info(Pelanggan $pelanggan): JsonResponse
    {
        return response()->json([
            'batas_kredit' => $pelanggan->batas_kredit,
            'piutang_aktif' => $pelanggan->tagihan()
                ->where('status', 'belum_lunas')
                ->sum('total_tagihan'),
        ]);
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
