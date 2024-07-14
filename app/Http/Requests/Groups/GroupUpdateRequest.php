<?php

namespace App\Http\Requests\Groups;

use Illuminate\Foundation\Http\FormRequest;

class GroupUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => ['nullable','string','max:10000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
