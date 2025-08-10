<?php

namespace App\Jobs\Nextcloud;

use App\Models\Group;
use App\Services\NextcloudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateGroupJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public function __construct(
        public Group $group,
        public bool $isTeamGroup = false,
        public ?int $parentFolderId = null
    ) {}

    public function handle(): void
    {
        try {
            NextcloudService::createGroup($this->group->hashid);

            if ($this->isTeamGroup && $this->group->parent && $this->parentFolderId) {
                // Team group: Set display name with parent
                NextcloudService::setDisplayName(
                    $this->group->hashid,
                    $this->group->parent->name . ' / ' . $this->group->name
                );

                // Add to parent group folder
                NextcloudService::addGroupToFolder($this->parentFolderId, $this->group->hashid);
            } else {
                // Regular group
                NextcloudService::setDisplayName($this->group->hashid, $this->group->name);
            }

            Log::info('Nextcloud group created successfully', [
                'group_id' => $this->group->id,
                'group_hashid' => $this->group->hashid,
                'is_team_group' => $this->isTeamGroup,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create Nextcloud group', [
                'group_id' => $this->group->id,
                'group_hashid' => $this->group->hashid,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Nextcloud group creation job failed permanently', [
            'group_id' => $this->group->id,
            'group_hashid' => $this->group->hashid,
            'error' => $exception->getMessage(),
        ]);
    }
}
