<?php

namespace App\Http\Requests\Notif;

use Illuminate\Foundation\Http\FormRequest;

class CountNotifRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'role' => ['nullable', 'string'],
        ];
    }
}
