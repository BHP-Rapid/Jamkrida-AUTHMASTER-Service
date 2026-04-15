<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class LampiranSettingsMenuRequest extends FormRequest
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
