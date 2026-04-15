<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mitra_id' => ['required', 'string', 'max:10'],
            'module' => ['required', 'string', 'max:20'],
            'product_details' => ['required', 'array', 'min:1'],
            'product_details.*.product_id' => ['required', 'string'],
            'product_details.*.key' => ['required', 'string'],
            'product_details.*.value' => ['nullable', 'string'],
            'product_details.*.lampiran' => ['nullable', 'string'],
            'product_details.*.reason_claim' => ['nullable', 'string'],
            'product_details.*.is_mandatory' => ['required', 'integer'],
        ];
    }
}
