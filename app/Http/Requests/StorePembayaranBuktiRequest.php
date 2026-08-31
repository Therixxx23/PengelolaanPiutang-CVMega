<?php

namespace App\Http\Requests;

use App\Models\Tagihan;
use Illuminate\Foundation\Http\FormRequest;

class StorePembayaranBuktiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSales();
    }

    public function rules(): array
    {
        $tagihan = Tagihan::find($this->input('tagihan_id'));

        $maksimum = '999999999999.99';

        if ($tagihan !== null) {
            $totalDibayar = (string) $tagihan->pembayaran()->sum('jumlah_bayar');
            $sisa = bcsub((string) $tagihan->total_tagihan, $totalDibayar, 2);
            $maksimum = bccomp($sisa, '0', 2) < 0 ? '0' : $sisa;
        }

        return [
            'tagihan_id' => [
                'required',
                'exists:tagihan,id_tagihan',
                function ($attribute, $value, $fail) {
                    $tagihan = Tagihan::find($value);

                    if ($tagihan === null) {
                        return;
                    }

                    if ($tagihan->assigned_sales_id !== $this->user()->id) {
                        $fail('Tagihan ini bukan tanggung jawab Anda.');
                    }

                    if ($tagihan->approval_status !== 'aktif') {
                        $fail('Tagihan belum aktif dan tidak bisa menerima bukti pembayaran.');
                    }

                    if ($tagihan->status === 'lunas') {
                        $fail('Tagihan sudah lunas.');
                    }
                },
            ],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'nominal_dibayar' => ['required', 'numeric', 'min:1000', 'max:'.$maksimum],
            'tanggal_bayar' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'tagihan_id.required' => 'Tagihan wajib dipilih.',
            'tagihan_id.exists' => 'Tagihan tidak ditemukan.',
            'file.required' => 'File bukti wajib diunggah.',
            'file.mimes' => 'Format file harus JPG, PNG, atau PDF.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
            'nominal_dibayar.required' => 'Nominal dibayar wajib diisi.',
            'nominal_dibayar.min' => 'Nominal minimal Rp 1.000.',
            'nominal_dibayar.max' => 'Nominal melebihi sisa tagihan.',
            'tanggal_bayar.required' => 'Tanggal bayar wajib diisi.',
            'tanggal_bayar.before_or_equal' => 'Tanggal bayar tidak boleh di masa depan.',
        ];
    }
}
