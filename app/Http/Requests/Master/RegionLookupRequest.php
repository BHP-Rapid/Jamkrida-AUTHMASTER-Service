<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class RegionLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'province_code' => ['nullable', 'string'],
            'regency_code' => ['nullable', 'string'],
            'district_code' => ['nullable', 'string'],
        ];
    }
}
