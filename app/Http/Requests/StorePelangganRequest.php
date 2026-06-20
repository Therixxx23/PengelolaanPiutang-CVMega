<?php

namespace App\Http\Requests;

use App\Models\Pelanggan;
use Illuminate\Foundation\Http\FormRequest;

class StorePelangganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Pelanggan::class);
    }

    public function rules(): array
    {
        return [
            'nama_pelanggan' => ['required', 'string', 'max:150'],
            'alamat' => ['nullable', 'string'],
            'wilayah' => ['nullable', 'string', 'max:100'],
            'no_telepon' => ['nullable', 'string', 'max:20'],
            'batas_kredit' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_pelanggan.required' => 'Nama pelanggan wajib diisi.',
            'batas_kredit.numeric' => 'Batas kredit harus berupa angka.',
        ];
    }
}
