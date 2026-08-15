<?php

namespace App\Http\Requests;

use App\Models\Pelanggan;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTagihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('tagihan'));
    }

    public function rules(): array
    {
        return [
            'id_pelanggan' => ['required', 'exists:'.Pelanggan::class.',id_pelanggan'],
            'tanggal_tagihan' => ['required', 'date', 'before_or_equal:today'],
            'tanggal_jatuh_tempo' => ['required', 'date', 'after:tanggal_tagihan'],
            'total_tagihan' => ['required', 'numeric', 'min:1000', 'max:999999999999', function ($attribute, $value, $fail) {
                $tagihan = $this->route('tagihan');
                $totalDibayar = (string) $tagihan->pembayaran()->sum('jumlah_bayar');

                if (bccomp((string) $value, $totalDibayar, 2) < 0) {
                    $fail('Total tagihan tidak boleh kurang dari jumlah yang sudah dibayar (Rp '.number_format((float) $totalDibayar, 0, ',', '.').').');
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'id_pelanggan.required' => 'Pelanggan wajib dipilih.',
            'id_pelanggan.exists' => 'Pelanggan tidak ditemukan.',
            'tanggal_tagihan.required' => 'Tanggal tagihan wajib diisi.',
            'tanggal_tagihan.before_or_equal' => 'Tanggal tagihan tidak boleh di masa depan.',
            'tanggal_jatuh_tempo.required' => 'Tanggal jatuh tempo wajib diisi.',
            'tanggal_jatuh_tempo.after' => 'Jatuh tempo harus setelah tanggal tagihan.',
            'total_tagihan.required' => 'Total tagihan wajib diisi.',
            'total_tagihan.min' => 'Nilai tagihan minimal Rp 1.000.',
            'total_tagihan.max' => 'Nilai tagihan terlalu besar.',
        ];
    }
}
