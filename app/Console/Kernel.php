<?php

namespace App\Console;

use App\Console\Commands\appsSyncCommand;
use App\Console\Commands\ClearUnverifiedCommand;
use App\Console\Commands\PruneExpiredMetadataCommand;
use App\Console\Commands\User\UserChangePasswordCommand;
use App\Jobs\PurgeOldNotificationsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        appsSyncCommand::class,
        ClearUnverifiedCommand::class,
        PruneExpiredMetadataCommand::class,
        UserChangePasswordCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('clear:unverified')->hourly();
        $schedule->command('metadata:prune-expired')->daily();
        $schedule->command('model:prune')->daily();
        $schedule->job(new PurgeOldNotificationsJob())->dailyAt('03:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
