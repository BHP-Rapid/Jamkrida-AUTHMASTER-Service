<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class MappingValueTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => ['nullable', 'string', 'in:asc,desc'],
            'sort_column' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'show_page' => ['nullable', 'integer', 'min:1'],
            'filter' => ['nullable', 'array'],
        ];
    }
}
