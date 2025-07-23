<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PbcRequest;
use App\Models\DocumentUpload;
use App\Models\Client;

class GenerateMonthlyReport extends Command
{
    protected $signature = 'pbc:generate-monthly-report {--month=} {--year=}';
    protected $description = 'Generate monthly progress reports for PBC system';

    public function handle()
    {
        $month = $this->option('month') ?? now()->month;
        $year = $this->option('year') ?? now()->year;

        $this->info("Generating report for {$month}/{$year}...");

        // Generate statistics
        $stats = [
            'total_requests' => PbcRequest::whereMonth('created_at', $month)
                ->whereYear('created_at', $year)->count(),
            'completed_requests' => PbcRequest::whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->where('status', 'completed')->count(),
            'overdue_requests' => PbcRequest::whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->where('status', 'overdue')->count(),
            'documents_uploaded' => DocumentUpload::whereMonth('created_at', $month)
                ->whereYear('created_at', $year)->count(),
            'active_clients' => Client::whereHas('pbcRequests', function($query) use ($month, $year) {
                $query->whereMonth('created_at', $month)
                      ->whereYear('created_at', $year);
            })->count(),
        ];

        // Display report
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Requests Created', $stats['total_requests']],
                ['Completed Requests', $stats['completed_requests']],
                ['Overdue Requests', $stats['overdue_requests']],
                ['Documents Uploaded', $stats['documents_uploaded']],
                ['Active Clients', $stats['active_clients']],
            ]
        );

        $this->info('Monthly report generated successfully.');
        return 0;
    }
}
