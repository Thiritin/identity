<?php

namespace App\Http\Resources\V2;

use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StaffResourceCollection extends AnonymousResourceCollection
{
    private ?User $viewer = null;

    public function forViewer(User $viewer): static
    {
        $this->viewer = $viewer;

        return $this;
    }

    public function toArray($request): array
    {
        if ($this->viewer) {
            $this->collection->each(fn (StaffResource $resource) => $resource->forViewer($this->viewer));
        }

        return parent::toArray($request);
    }
}
