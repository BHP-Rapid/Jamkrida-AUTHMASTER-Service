<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*.mitra_id' => ['required', 'string'],
            '*.module' => ['required', 'string'],
            '*.product_id' => ['required', 'string'],
            '*.lampiran' => ['required', 'string'],
            '*.is_mandatory' => ['required', 'integer', 'in:0,1'],
        ];
    }
}
