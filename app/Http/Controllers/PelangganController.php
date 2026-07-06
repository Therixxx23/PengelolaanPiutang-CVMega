<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePelangganRequest;
use App\Http\Requests\UpdatePelangganRequest;
use App\Models\Pelanggan;

class PelangganController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Pelanggan::class);

        $pelanggan = Pelanggan::orderBy('nama_pelanggan')->paginate(15);

        return view('pelanggan.index', compact('pelanggan'));
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
