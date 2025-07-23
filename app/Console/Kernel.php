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
        // Update overdue PBC requests daily at midnight
        $schedule->command('pbc:update-overdue')
                 ->daily()
                 ->at('00:00')
                 ->description('Update overdue status for PBC requests');

        // Clean up temporary files weekly
        $schedule->command('pbc:cleanup-temp-files')
                 ->weekly()
                 ->sundays()
                 ->at('02:00')
                 ->description('Clean up temporary and orphaned files');

        // Generate progress reports monthly
        $schedule->command('pbc:generate-monthly-report')
                 ->monthly()
                 ->description('Generate monthly progress reports');

        // Send reminder emails for pending requests
        $schedule->command('pbc:send-reminders')
                 ->dailyAt('09:00')
                 ->description('Send reminder emails for pending PBC requests');

        // Laravel's default queue worker (if using queues)
        // $schedule->command('queue:work --stop-when-empty')->everyMinute();
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
