<?php

namespace App\Jobs\Nextcloud;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Services\NextcloudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateGroupJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public function __construct(
        public Group $group,
        public array $originalData,
        public array $changedFields
    ) {}

    public function handle(): void
    {
        try {
            // Handle folder name changes
            if (in_array('nextcloud_folder_name', $this->changedFields)) {
                if ($this->group->nextcloud_folder_id) {
                    // Update existing folder
                    NextcloudService::renameFolder($this->group->nextcloud_folder_id, $this->group->nextcloud_folder_name);
                } else {
                    // Create new folder and group
                    NextcloudService::createGroup($this->group->hashid);
                    $folderId = NextcloudService::createFolder(
                        $this->group->nextcloud_folder_name,
                        $this->group->hashid
                    );

                    $this->group->nextcloud_folder_id = $folderId;
                    $this->group->saveQuietly();

                    NextcloudService::setDisplayName($this->group->hashid, $this->group->name);

                    // Add all users to the group
                    foreach ($this->group->users as $user) {
                        NextcloudService::addUserToGroup($this->group, $user);

                        // Set ACL for admins and owners
                        if (in_array($user->pivot->level, [GroupUserLevel::Admin, GroupUserLevel::Owner])) {
                            NextcloudService::setManageAcl($this->group, $user, true);
                        }
                    }
                }
            }

            // Handle name changes for groups with folders
            if (in_array('name', $this->changedFields) && $this->group->nextcloud_folder_id) {
                if ($this->group->type === GroupTypeEnum::Team && $this->group->parent?->nextcloud_folder_id) {
                    // Team group: Update with parent name
                    NextcloudService::setDisplayName(
                        $this->group->hashid,
                        $this->group->parent->name . ' / ' . $this->group->name
                    );
                } else {
                    // Regular group
                    NextcloudService::setDisplayName($this->group->hashid, $this->group->name);
                }
            }

            Log::info('Nextcloud group updated successfully', [
                'group_id' => $this->group->id,
                'group_hashid' => $this->group->hashid,
                'changed_fields' => $this->changedFields,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update Nextcloud group', [
                'group_id' => $this->group->id,
                'group_hashid' => $this->group->hashid,
                'changed_fields' => $this->changedFields,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Nextcloud group update job failed permanently', [
            'group_id' => $this->group->id,
            'group_hashid' => $this->group->hashid,
            'changed_fields' => $this->changedFields,
            'error' => $exception->getMessage(),
        ]);
    }
}
