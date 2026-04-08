<?php

namespace App\Http\Requests\Api\v2;

use App\Models\User;
use App\Support\ScopeChecker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ScopeChecker::has('Attendances.ReadWrite');
    }

    public function rules(): array
    {
        $targetUser = $this->resolveTargetUser();

        return [
            'convention_id' => [
                'required',
                'integer',
                'exists:conventions,id',
                Rule::unique('convention_attendee')
                    ->where('user_id', $targetUser->id),
            ],
        ];
    }

    private function resolveTargetUser(): User
    {
        $userParam = $this->route('user');

        if ($userParam === 'me') {
            return $this->user();
        }

        return User::findByHashidOrFail($userParam);
    }
}
