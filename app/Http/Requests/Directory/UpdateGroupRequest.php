<?php

namespace App\Http\Requests\Directory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupRequest extends FormRequest
{
    public const ALLOWED_ICONS = [
        'users', 'shield', 'code', 'palette', 'music', 'camera',
        'heart', 'star', 'zap', 'globe', 'megaphone', 'wrench',
        'book', 'briefcase', 'building', 'truck', 'gamepad-2',
        'monitor', 'printer', 'scissors', 'headphones', 'mic',
        'video', 'pen-tool', 'layout', 'server', 'gift', 'coffee',
        'sparkles', 'clapperboard',
    ];

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('group'));
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:10000',
            'logo' => 'nullable|image|max:2048',
            'icon' => ['nullable', 'string', Rule::in(self::ALLOWED_ICONS)],
        ];
    }
}
