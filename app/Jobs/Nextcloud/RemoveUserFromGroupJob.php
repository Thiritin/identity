<?php

namespace App\Jobs\Nextcloud;

use App\Enums\GroupTypeEnum;
use App\Models\Group;
use App\Models\User;
use App\Services\NextcloudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RemoveUserFromGroupJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public function __construct(
        public Group $group,
        public User $user,
        public GroupTypeEnum $groupType
    ) {}

    public function handle(): void
    {
        try {
            NextcloudService::removeUserFromGroup($this->group, $this->user);

            // Remove ACL management permissions (but not for team groups)
            if ($this->groupType !== GroupTypeEnum::Team) {
                NextcloudService::setManageAcl($this->group, $this->user, false);
            }

            Log::info('User removed from Nextcloud group successfully', [
                'group_id' => $this->group->id,
                'group_hashid' => $this->group->hashid,
                'user_id' => $this->user->id,
                'user_hashid' => $this->user->hashid,
                'group_type' => $this->groupType->value,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to remove user from Nextcloud group', [
                'group_id' => $this->group->id,
                'group_hashid' => $this->group->hashid,
                'user_id' => $this->user->id,
                'user_hashid' => $this->user->hashid,
                'group_type' => $this->groupType->value,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Remove user from Nextcloud group job failed permanently', [
            'group_id' => $this->group->id,
            'group_hashid' => $this->group->hashid,
            'user_id' => $this->user->id,
            'user_hashid' => $this->user->hashid,
            'group_type' => $this->groupType->value,
            'error' => $exception->getMessage(),
        ]);
    }
}
