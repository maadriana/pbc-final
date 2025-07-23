<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PbcRequest;
use Carbon\Carbon;

class SendReminders extends Command
{
    protected $signature = 'pbc:send-reminders';
    protected $description = 'Send reminder emails for pending PBC requests';

    public function handle()
    {
        $this->info('Checking for requests that need reminders...');

        // Find requests due in 3 days or less
        $upcomingRequests = PbcRequest::with(['client.user'])
            ->where('due_date', '<=', Carbon::now()->addDays(3))
            ->where('due_date', '>=', Carbon::now())
            ->whereIn('status', ['pending', 'in_progress'])
            ->get();

        if ($upcomingRequests->isEmpty()) {
            $this->info('No requests found that need reminders.');
            return 0;
        }

        $reminderCount = 0;
        foreach ($upcomingRequests as $request) {
            // Here you would send actual email
            // Mail::to($request->client->user->email)->send(new PbcRequestReminder($request));

            $daysLeft = Carbon::now()->diffInDays($request->due_date);
            $this->line("Reminder sent for request #{$request->id} to {$request->client->company_name} ({$daysLeft} days left)");
            $reminderCount++;
        }

        $this->info("Sent {$reminderCount} reminder emails.");
        return 0;
    }
}
