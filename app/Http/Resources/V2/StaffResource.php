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
            'nda_verified' => ! is_null($this->nda_verified_at),
        ];

        $piiFields = [
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'birthdate' => $this->birthdate?->toDateString(),
            'phone' => $this->phone,
            'telegram_username' => $this->telegram_username,
            'spoken_languages' => $this->spoken_languages,
            'credit_as' => $this->credit_as,
        ];

        foreach ($piiFields as $field => $value) {
            if ($isSelf || $this->resource->canViewStaffField($field, $viewer)) {
                $data[$field] = $value;
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
