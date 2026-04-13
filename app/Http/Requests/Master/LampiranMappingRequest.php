<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class LampiranMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis_mitra' => ['required', 'string', 'size:3'],
            'module' => ['required', 'string'],
            'jenis_produk' => ['required', 'string', 'max:4'],
        ];
    }
}
