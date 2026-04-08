<?php

namespace App\Http\Resources\V2;

use App\Support\ScopeChecker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserStaffResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $data = [];

        if (ScopeChecker::has('Staff.Profile.Read')) {
            $data['name'] = $this->name;
            $data['firstname'] = $this->firstname;
            $data['lastname'] = $this->lastname;
            $data['birthdate'] = $this->birthdate?->toDateString();
            $data['avatar'] = $this->profile_photo_path
                ? Storage::disk('s3-avatars')->url($this->profile_photo_path)
                : null;
            $data['pronouns'] = $this->pronouns;
            $data['credit_as'] = $this->credit_as;
            $data['nda_checked_at'] = $this->nda_checked_at?->toIso8601String();
        }

        if (ScopeChecker::has('Staff.Skills.Read')) {
            $data['spoken_languages'] = $this->spoken_languages ?? [];
            $data['skills'] = $this->skills->pluck('name')->values()->all();
        }

        if (ScopeChecker::has('Staff.Contact.Read')) {
            $data['phone'] = $this->phone;
            $data['telegram_username'] = $this->telegram_username;
        }

        if (ScopeChecker::has('Staff.Address.Read')) {
            $data['address_line1'] = $this->address_line1;
            $data['address_line2'] = $this->address_line2;
            $data['city'] = $this->city;
            $data['postal_code'] = $this->postal_code;
            $data['country'] = $this->country;
        }

        if (ScopeChecker::has('Staff.Emergency.Read')) {
            $data['emergency_contact'] = [
                'name' => $this->emergency_contact_name,
                'phone' => $this->emergency_contact_phone,
                'telegram' => $this->emergency_contact_telegram,
            ];
        }

        return $data;
    }
}
