<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMitraUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string'],
            'email' => ['required', 'email'],
            'name' => ['required', 'string'],
            'phone' => ['nullable', 'string'],
        ];
    }
}
