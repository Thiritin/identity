<?php

namespace App\Http\Requests\Api\v2;

use App\Support\ScopeChecker;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ScopeChecker::has('Attendances.ReadWrite');
    }

    public function rules(): array
    {
        return [
            'is_attended' => ['sometimes', 'boolean'],
            'is_staff' => ['sometimes', 'boolean'],
        ];
    }
}
