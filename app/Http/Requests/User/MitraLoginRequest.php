<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class MitraLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
