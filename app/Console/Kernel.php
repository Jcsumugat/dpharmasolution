<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Update stock quantities daily at 2 AM
        $schedule->command('products:update-stock')->daily()->at('02:00');

        // Generate expiration reports weekly on Monday at 8 AM
        $schedule->command('batches:cleanup-expired')->weekly()->mondays()->at('08:00');

        // Optional: Auto-cleanup expired batches daily at 3 AM
        $schedule->command('batches:cleanup-expired --auto')->daily()->at('03:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
