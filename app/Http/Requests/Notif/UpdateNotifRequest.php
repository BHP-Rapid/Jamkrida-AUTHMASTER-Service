<?php

namespace App\Http\Requests\Notif;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotifRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dataId' => ['required', 'integer'],
        ];
    }
}
