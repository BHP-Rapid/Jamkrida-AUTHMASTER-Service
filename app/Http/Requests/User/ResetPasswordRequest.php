<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'user_type' => ['required', 'string', 'in:admin,mitra'],
        ];
    }
}
