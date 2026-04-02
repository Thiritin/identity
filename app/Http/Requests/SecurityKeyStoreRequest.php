<?php

namespace App\Http\Requests;

use App\Enums\TwoFactorTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class SecurityKeyStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'credential' => 'required|string',
            'name' => 'required|string|max:80',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $count = $this->user()->twoFactors()
                ->where('type', TwoFactorTypeEnum::SECURITY_KEY)
                ->count();

            if ($count >= 10) {
                $validator->errors()->add('credential', 'Maximum number of security keys reached (10).');
            }
        });
    }
}
