<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Services\ImportSiplahService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function __construct(
        protected ImportSiplahService $importService,
    ) {}

    public function index()
    {
        $this->authorize('import', Tagihan::class);

        return view('import.index');
    }

    public function preview(Request $request)
    {
        $this->authorize('import', Tagihan::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:2048'],
        ]);

        $file = $validated['file'];
        $path = $file->store('imports', 'local');

        try {
            $preview = $this->importService->preview(
                Storage::disk('local')->path($path)
            );
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        session(['import.file_path' => $path]);

        return view('import.preview', $preview);
    }

    public function store()
    {
        $this->authorize('import', Tagihan::class);

        $path = session('import.file_path');

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return redirect()
                ->route('import.index')
                ->with('error', 'Sesi impor kedaluwarsa. Silakan unggah ulang file Anda.');
        }

        $result = $this->importService->import(
            Storage::disk('local')->path($path),
            auth()->user()
        );

        Storage::disk('local')->delete($path);
        session()->forget('import.file_path');

        if ($result['success']) {
            return redirect()
                ->route('import.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('import.index')
            ->with('error', $result['message']);
    }

    public function cancel()
    {
        $this->authorize('import', Tagihan::class);

        $path = session('import.file_path');

        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        session()->forget('import.file_path');

        return redirect()
            ->route('import.index')
            ->with('info', 'Impor dibatalkan.');
    }
}
