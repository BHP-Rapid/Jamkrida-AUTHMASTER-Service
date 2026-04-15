<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ShowSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module' => ['required', 'string'],
            'mitra_id' => ['required', 'string'],
            'product_id' => ['required', 'string'],
        ];
    }
}
