<?php

namespace App\Http\Requests\Directory;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('group'));
    }

    public function rules(): array
    {
        return [
            'user_hashid' => 'required|string|exists:users,hashid',
        ];
    }
}
