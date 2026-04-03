<?php

namespace App\Http\Requests\Directory;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('group'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
        ];
    }
}
