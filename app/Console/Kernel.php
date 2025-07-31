<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\SendReminders::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Send reminders daily at 9 AM
        $schedule->command('reminders:send')
                 ->dailyAt('09:00')
                 ->name('daily-reminders')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Clean up old reminders weekly
        $schedule->command('reminders:send')
                 ->weekly()
                 ->name('weekly-reminder-cleanup')
                 ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
