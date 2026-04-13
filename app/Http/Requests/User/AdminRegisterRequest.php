<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class AdminRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mitra_id' => ['nullable', 'string'],
            'role' => ['required', 'string', 'in:admin_mitra,admin,super_admin'],
            'email' => ['required', 'email', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
