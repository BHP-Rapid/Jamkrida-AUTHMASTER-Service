<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ValidateResetUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url_key' => ['required', 'string'],
            'user_type' => ['required', 'string', 'in:admin,mitra'],
        ];
    }
}
