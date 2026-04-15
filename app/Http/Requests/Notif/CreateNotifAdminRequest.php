<?php

namespace App\Http\Requests\Notif;

use Illuminate\Foundation\Http\FormRequest;

class CreateNotifAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'message' => ['required', 'string'],
            'recipientType' => ['required', 'string', 'in:all,selected'],
            'recipient' => ['nullable', 'array'],
            'recipient.*' => ['string'],
        ];
    }
}
