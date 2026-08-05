<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        private ApprovalService $approvalService
    ) {}

    public function index()
    {
        $this->authorize('viewApproval', Tagihan::class);

        $tagihan = Tagihan::menungguApproval()
            ->with(['pelanggan', 'approvedBy'])
            ->latest('created_at')
            ->paginate(10);

        return view('approval.index', compact('tagihan'));
    }

    public function setujui(Tagihan $tagihan)
    {
        $this->authorize('approve', $tagihan);

        try {
            $this->approvalService->setujui($tagihan, auth()->user());

            return back()->with('success', "Tagihan {$tagihan->no_invoice} berhasil disetujui.");
        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function tolak(Request $request, Tagihan $tagihan)
    {
        $this->authorize('approve', $tagihan);

        $request->validate([
            'approval_note' => 'required|string|min:10|max:500',
        ], [
            'approval_note.required' => 'Alasan penolakan wajib diisi.',
            'approval_note.min' => 'Alasan minimal 10 karakter.',
        ]);

        try {
            $this->approvalService->tolak(
                $tagihan,
                auth()->user(),
                $request->approval_note
            );

            return back()->with('success', "Tagihan {$tagihan->no_invoice} telah ditolak.");
        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
