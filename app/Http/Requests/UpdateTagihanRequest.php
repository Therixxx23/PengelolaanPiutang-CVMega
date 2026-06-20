<?php

namespace App\Http\Requests;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('tagihan'));
    }

    public function rules(): array
    {
        return [
            'id_pelanggan' => ['required', 'exists:' . Pelanggan::class . ',id_pelanggan'],
            'no_invoice' => ['required', 'string', 'max:30', Rule::unique(Tagihan::class)->ignore($this->route('tagihan'))],
            'tanggal_tagihan' => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['required', 'date', 'after_or_equal:tanggal_tagihan'],
            'total_tagihan' => ['required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:belum_lunas,lunas'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_pelanggan.required' => 'Pilih pelanggan.',
            'total_tagihan.required' => 'Total tagihan wajib diisi.',
            'tanggal_jatuh_tempo.after_or_equal' => 'Jatuh tempo harus setelah atau sama dengan tanggal tagihan.',
        ];
    }
}
