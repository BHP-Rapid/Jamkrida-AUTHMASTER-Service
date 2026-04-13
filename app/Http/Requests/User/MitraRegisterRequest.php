<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class MitraRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mitra_id' => ['required', 'string'],
            'role' => ['required', 'string', 'in:pusat,cabang,admin,head_admin_mitra,mitra'],
            'email' => ['required', 'email'],
            'name' => ['required', 'string'],
            'phone' => ['nullable', 'string'],
        ];
    }
}
