<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;

class LogAktivitasController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', LogAktivitas::class);

        $logs = LogAktivitas::with('user')
            ->latest('created_at')
            ->paginate(20);

        return view('log-aktivitas.index', compact('logs'));
    }
}
