<?php

namespace App\Http\Requests\Auth;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function rules()
    {
        return [
            "username" => [
                "min:3",
                "max:25",
                "required",
                "string",
                Rule::unique('users', function (Builder $table, string $value) {
                    $table->where('name', 'ILIKE', $value);
                })
            ],
            "email" => [
                "email",
                "required",
                "unique:users,email"
            ],
        ];
    }
}
