<?php

namespace App\Jobs\Nextcloud;

use App\Services\NextcloudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteGroupJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public function __construct(
        public string $groupHashid,
        public int $groupId
    ) {}

    public function handle(): void
    {
        try {
            NextcloudService::deleteGroup($this->groupHashid);

            Log::info('Nextcloud group deleted successfully', [
                'group_id' => $this->groupId,
                'group_hashid' => $this->groupHashid,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete Nextcloud group', [
                'group_id' => $this->groupId,
                'group_hashid' => $this->groupHashid,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Nextcloud group deletion job failed permanently', [
            'group_id' => $this->groupId,
            'group_hashid' => $this->groupHashid,
            'error' => $exception->getMessage(),
        ]);
    }
}
