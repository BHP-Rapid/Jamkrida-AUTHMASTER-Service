<?php

namespace App\Http\Requests\Notif;

use Illuminate\Foundation\Http\FormRequest;

class GetNotifRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'role' => ['nullable', 'string'],
        ];
    }
}
