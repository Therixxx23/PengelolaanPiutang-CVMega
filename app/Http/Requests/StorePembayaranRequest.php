<?php

namespace App\Http\Requests;

use App\Models\Pembayaran;
use Illuminate\Foundation\Http\FormRequest;

class StorePembayaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Pembayaran::class);
    }

    public function rules(): array
    {
        $tagihan = $this->route('tagihan');

        $sudahDibayar = $tagihan ? (float) $tagihan->pembayaran()->sum('jumlah_bayar') : 0;
        $sisaTagihan = $tagihan ? (float) $tagihan->total_tagihan - $sudahDibayar : 0;

        return [
            'tanggal_bayar' => ['required', 'date', 'before_or_equal:today'],
            'jumlah_bayar' => ['required', 'numeric', 'min:1000', 'max:'.max(0, $sisaTagihan)],
            'metode_bayar' => ['required', 'in:tunai,transfer,giro,cek'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_bayar.before_or_equal' => 'Tanggal bayar tidak boleh di masa depan.',
            'jumlah_bayar.required' => 'Jumlah bayar wajib diisi.',
            'jumlah_bayar.min' => 'Jumlah bayar minimal Rp 1.000.',
            'jumlah_bayar.max' => 'Jumlah bayar melebihi sisa tagihan.',
            'metode_bayar.required' => 'Metode bayar wajib diisi.',
            'metode_bayar.in' => 'Metode bayar tidak valid.',
        ];
    }
}
