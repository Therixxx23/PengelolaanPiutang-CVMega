<?php

namespace App\Services;

use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    public function generate(): string
    {
        $now = now();
        $prefix = 'INV/'.$now->format('Y/m/');

        $lastInvoice = Tagihan::where('no_invoice', 'like', $prefix.'%')
            ->select(DB::raw('MAX(no_invoice) as last'))
            ->value('last');

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice, -6);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
