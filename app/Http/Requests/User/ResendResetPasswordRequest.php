<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ResendResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_type' => ['required', 'string', 'in:admin,mitra'],
            'user_id' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
        ];
    }
}
