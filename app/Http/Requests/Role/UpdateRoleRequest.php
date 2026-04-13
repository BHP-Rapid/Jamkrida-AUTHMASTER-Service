<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', 'min:1'],
            'payload' => ['required', 'array', 'min:1'],
            'payload.*.menu_id' => ['required', 'integer', 'min:1'],
            'payload.*.action' => ['array'],
            'payload.*.action.*' => ['required', 'string'],
        ];
    }
}
