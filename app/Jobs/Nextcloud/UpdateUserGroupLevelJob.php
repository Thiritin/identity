<?php

namespace App\Jobs\Nextcloud;

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use App\Services\NextcloudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateUserGroupLevelJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public function __construct(
        public Group $group,
        public User $user,
        public GroupUserLevel $newLevel,
        public GroupUserLevel $oldLevel
    ) {}

    public function handle(): void
    {
        try {
            $allowAclManagement = in_array($this->newLevel, [GroupUserLevel::Admin, GroupUserLevel::Owner]);
            NextcloudService::setManageAcl($this->group, $this->user, $allowAclManagement);

            Log::info('User group level updated in Nextcloud successfully', [
                'group_id' => $this->group->id,
                'group_hashid' => $this->group->hashid,
                'user_id' => $this->user->id,
                'user_hashid' => $this->user->hashid,
                'old_level' => $this->oldLevel->value,
                'new_level' => $this->newLevel->value,
                'acl_management' => $allowAclManagement,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user group level in Nextcloud', [
                'group_id' => $this->group->id,
                'group_hashid' => $this->group->hashid,
                'user_id' => $this->user->id,
                'user_hashid' => $this->user->hashid,
                'old_level' => $this->oldLevel->value,
                'new_level' => $this->newLevel->value,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Update user group level in Nextcloud job failed permanently', [
            'group_id' => $this->group->id,
            'group_hashid' => $this->group->hashid,
            'user_id' => $this->user->id,
            'user_hashid' => $this->user->hashid,
            'old_level' => $this->oldLevel->value,
            'new_level' => $this->newLevel->value,
            'error' => $exception->getMessage(),
        ]);
    }
}
