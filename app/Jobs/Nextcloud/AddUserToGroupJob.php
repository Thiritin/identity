<?php

namespace App\Jobs\Nextcloud;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use App\Services\NextcloudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AddUserToGroupJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public function __construct(
        public Group $group,
        public User $user,
        public GroupUserLevel $level
    ) {}

    public function handle(): void
    {
        try {
            NextcloudService::addUserToGroup($this->group, $this->user);

            // Set ACL management permissions for admins and owners (but not for team groups)
            $allowAclManagement = in_array($this->level, [GroupUserLevel::Admin, GroupUserLevel::Owner]);
            if ($allowAclManagement && $this->group->type !== GroupTypeEnum::Team) {
                NextcloudService::setManageAcl($this->group, $this->user, true);
            }

            Log::info('User added to Nextcloud group successfully', [
                'group_id' => $this->group->id,
                'group_hashid' => $this->group->hashid,
                'user_id' => $this->user->id,
                'user_hashid' => $this->user->hashid,
                'level' => $this->level->value,
                'acl_management' => $allowAclManagement && $this->group->type !== GroupTypeEnum::Team,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add user to Nextcloud group', [
                'group_id' => $this->group->id,
                'group_hashid' => $this->group->hashid,
                'user_id' => $this->user->id,
                'user_hashid' => $this->user->hashid,
                'level' => $this->level->value,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Add user to Nextcloud group job failed permanently', [
            'group_id' => $this->group->id,
            'group_hashid' => $this->group->hashid,
            'user_id' => $this->user->id,
            'user_hashid' => $this->user->hashid,
            'level' => $this->level->value,
            'error' => $exception->getMessage(),
        ]);
    }
}
