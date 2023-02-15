<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class EmailVerificationRequest extends FormRequest
{
    private $user;

    public function rules(): array
    {
        return [

        ];
    }

    public function authorize(): bool
    {
        $this->user = User::findByHashid($this->route('id'));
        if (!hash_equals(sha1($this->user->getEmailForVerification()), (string)$this->route('hash'))) {
            return false;
        }
        return true;
    }

    public function verifyUser(): User
    {
        return $this->user;
    }
}
