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
            'nama_pelanggan' => ['required', 'string', 'min:2', 'max:150', 'unique:'.Pelanggan::class.',nama_pelanggan'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'wilayah' => ['required', 'string', 'max:100'],
            'no_telepon' => ['required', 'string', 'min:8', 'max:20', 'regex:/^[0-9\-\+\(\)\s]+$/'],
            'batas_kredit' => ['required', 'numeric', 'min:0', 'max:999999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_pelanggan.required' => 'Nama pelanggan wajib diisi.',
            'nama_pelanggan.unique' => 'Nama pelanggan sudah terdaftar.',
            'nama_pelanggan.min' => 'Nama pelanggan minimal 2 karakter.',
            'wilayah.required' => 'Wilayah wajib diisi.',
            'no_telepon.required' => 'Nomor telepon wajib diisi.',
            'no_telepon.regex' => 'Format nomor telepon tidak valid.',
            'batas_kredit.required' => 'Batas kredit wajib diisi.',
            'batas_kredit.min' => 'Batas kredit tidak boleh negatif.',
            'batas_kredit.numeric' => 'Batas kredit harus berupa angka.',
        ];
    }
}
