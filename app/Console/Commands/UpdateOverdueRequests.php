<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PbcRequest;
use Carbon\Carbon;

class UpdateOverdueRequests extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pbc:update-overdue';

    /**
     * The console command description.
     */
    protected $description = 'Update overdue status for PBC requests that have passed their due date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting overdue requests update...');

        // Find requests that are overdue
        $overdueRequests = PbcRequest::where('due_date', '<', Carbon::now())
            ->whereNotIn('status', ['completed', 'overdue'])
            ->get();

        if ($overdueRequests->isEmpty()) {
            $this->info('No requests found to mark as overdue.');
            return 0;
        }

        $count = 0;
        foreach ($overdueRequests as $request) {
            $request->update(['status' => 'overdue']);
            $count++;

            $this->line("Marked request #{$request->id} ({$request->title}) as overdue");
        }

        $this->info("Successfully updated {$count} requests to overdue status.");
        return 0;
    }
}
