<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMandatorySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mitra_id' => ['required', 'string'],
            'generalSettings' => ['nullable', 'array'],
            'lampiran' => ['nullable', 'array'],
            'reasonClaim' => ['nullable', 'array'],
        ];
    }
}
