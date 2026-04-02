<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupUserStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required_without:email|prohibits:email',
            'email' => 'email|required_without:id|exists:users,email|prohibits:id',
            'level' => [
                'nullable',
                Rule::in(['member', 'admin']),
            ],
        ];
    }
}
