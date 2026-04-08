<?php

namespace App\Http\Controllers\Api\v2\Users;

use App\Http\Controllers\Api\v2\Concerns\ChecksScopes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v2\UpdateStaffProfileRequest;
use App\Http\Resources\V2\UserStaffResource;
use App\Models\Skill;
use Illuminate\Http\Request;

class UserStaffController extends Controller
{
    use ChecksScopes;

    public function show(Request $request, string $user)
    {
        $this->requireScope('Staff.Profile.Read');

        $target = $this->resolveUser($request, $user);

        if ($target->id !== $request->user()->id) {
            $this->requireScope('Staff.Profile.Read.All');
        }

        $target->load('skills');

        return response()->json(
            (new UserStaffResource($target))->toArray($request),
        );
    }

    public function update(UpdateStaffProfileRequest $request, string $user)
    {
        $this->requireScope('Staff.Profile.ReadWrite');

        $target = $this->resolveUser($request, $user);
        $isSelf = $target->id === $request->user()->id;

        if (! $isSelf) {
            $this->requireScope('Staff.Profile.ReadWrite.All');
            $this->authorizeManageUser($request->user(), $target);
        }

        $validated = $request->validated();
        $updates = [];

        // Profile fields
        $profileFields = ['firstname', 'lastname', 'birthdate', 'pronouns', 'credit_as'];
        foreach ($profileFields as $field) {
            if (array_key_exists($field, $validated)) {
                $this->requireScope('Staff.Profile.ReadWrite');
                $updates[$field] = $validated[$field];
            }
        }

        // Skills fields
        $syncSkills = false;
        if (array_key_exists('spoken_languages', $validated)) {
            $this->requireScope('Staff.Skills.ReadWrite');
            $updates['spoken_languages'] = $validated['spoken_languages'];
        }
        if (array_key_exists('skills', $validated)) {
            $this->requireScope('Staff.Skills.ReadWrite');
            $syncSkills = true;
        }

        // Contact fields
        $contactFields = ['phone', 'telegram_username'];
        foreach ($contactFields as $field) {
            if (array_key_exists($field, $validated)) {
                $this->requireScope('Staff.Contact.ReadWrite');
                $updates[$field] = $validated[$field];
            }
        }

        // Address fields
        $addressFields = ['address_line1', 'address_line2', 'city', 'postal_code', 'country'];
        foreach ($addressFields as $field) {
            if (array_key_exists($field, $validated)) {
                $this->requireScope('Staff.Address.ReadWrite');
                $updates[$field] = $validated[$field];
            }
        }

        // Emergency contact (nested -> flat)
        if (array_key_exists('emergency_contact', $validated)) {
            $this->requireScope('Staff.Emergency.ReadWrite');
            $ec = $validated['emergency_contact'];
            if (array_key_exists('name', $ec)) {
                $updates['emergency_contact_name'] = $ec['name'];
            }
            if (array_key_exists('phone', $ec)) {
                $updates['emergency_contact_phone'] = $ec['phone'];
            }
            if (array_key_exists('telegram', $ec)) {
                $updates['emergency_contact_telegram'] = $ec['telegram'];
            }
        }

        if (! empty($updates)) {
            $target->update($updates);
        }

        if ($syncSkills) {
            $skillNames = $validated['skills'];
            $skillIds = collect($skillNames)->map(function ($name) {
                return Skill::firstOrCreate(['name' => $name])->id;
            });
            $target->skills()->sync($skillIds);
        }

        $fresh = $target->fresh();
        $fresh->load('skills');

        return response()->json(
            (new UserStaffResource($fresh))->toArray($request),
        );
    }
}
