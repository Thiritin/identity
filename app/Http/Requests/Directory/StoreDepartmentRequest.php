<?php

namespace App\Http\Requests\Directory;

use App\Support\Directory\DirectoryAuthorizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(DirectoryAuthorizer::class)
            ->canCreateChildGroup($this->user(), $this->route('group'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
        ];
    }
}
