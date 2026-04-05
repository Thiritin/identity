<?php

namespace App\Http\Resources\V2;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin User */
class StaffResource extends JsonResource
{
    public static $wrap = null;

    private ?User $viewer = null;

    public function forViewer(User $viewer): static
    {
        $this->viewer = $viewer;

        return $this;
    }

    /**
     * @param  AnonymousResourceCollection  $collection
     */
    public static function collection($resource): StaffResourceCollection
    {
        return new StaffResourceCollection($resource, static::class);
    }

    public function toArray(Request $request): array
    {
        $viewer = $this->viewer ?? $request->user();
        $isSelf = $viewer->id === $this->id;

        $data = [
            'id' => $this->hashid,
            'name' => $this->name,
            'avatar' => $this->profile_photo_path
                ? Storage::disk('s3-avatars')->url($this->profile_photo_path)
                : null,
            'nda_checked_at' => $this->nda_checked_at?->toIso8601String(),
        ];

        $piiGroups = [
            'firstname' => ['firstname' => $this->firstname],
            'lastname' => ['lastname' => $this->lastname],
            'pronouns' => ['pronouns' => $this->pronouns],
            'birthdate' => ['birthdate' => $this->birthdate?->toDateString()],
            'phone' => ['phone' => $this->phone],
            'telegram_username' => ['telegram_username' => $this->telegram_username],
            'spoken_languages' => ['spoken_languages' => $this->spoken_languages],
            'credit_as' => ['credit_as' => $this->credit_as],
            'address' => [
                'address_line1' => $this->address_line1,
                'address_line2' => $this->address_line2,
                'city' => $this->city,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ],
            'emergency_contact' => [
                'emergency_contact_name' => $this->emergency_contact_name,
                'emergency_contact_phone' => $this->emergency_contact_phone,
                'emergency_contact_telegram' => $this->emergency_contact_telegram,
            ],
        ];

        foreach ($piiGroups as $visibilityKey => $wireFields) {
            if ($isSelf || $this->resource->canViewStaffField($visibilityKey, $viewer)) {
                foreach ($wireFields as $wireKey => $value) {
                    $data[$wireKey] = $value;
                }
            }
        }

        if ($this->relationLoaded('groups')) {
            $data['groups'] = $this->groups->map(fn ($group) => [
                'id' => $group->hashid,
                'name' => $group->name,
                'type' => $group->type->value,
                'slug' => $group->slug,
                'level' => $group->pivot->level->value,
                'title' => $group->pivot->title,
            ])->values();
        }

        return $data;
    }
}
