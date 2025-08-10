<?php

namespace App\Observers;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Jobs\Nextcloud\CreateGroupJob;
use App\Jobs\Nextcloud\DeleteGroupJob;
use App\Jobs\Nextcloud\UpdateGroupJob;
use App\Models\Group;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class GroupObserver
{
    public function created(Group $group)
    {
        if (! App::isProduction() && $group->type === GroupTypeEnum::Team && $group->parent?->nextcloud_folder_id) {
            CreateGroupJob::dispatch($group, true, $group->parent->nextcloud_folder_id);

            return;
        }

        if (Auth::user()) {
            $group->users()->attach(Auth::user(), [
                'level' => GroupUserLevel::Owner,
            ]);
        }
    }

    public function updated(Group $group): void
    {
        if (! App::isProduction()) {
            return;
        }

        if (! app()->runningUnitTests()) {
            $changedFields = array_keys($group->getDirty());
            $relevantChanges = array_intersect($changedFields, ['nextcloud_folder_name', 'name']);

            if (! empty($relevantChanges)) {
                UpdateGroupJob::dispatch($group, $group->getOriginal(), $relevantChanges);
            }
        }
    }

    public function deleted(Group $group)
    {
        if (! App::isProduction()) {
            return;
        }

        DeleteGroupJob::dispatch($group->hashid, $group->id);
    }
}
