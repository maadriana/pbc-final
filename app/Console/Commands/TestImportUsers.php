<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\Client;
use App\Models\User;

class TestImportUsers extends Command
{
    protected $signature = 'test:import-users {project_id?} {client_id?}';
    protected $description = 'Test available users for Excel import';

    public function handle()
    {
        $projectId = $this->argument('project_id') ?? 1;
        $clientId = $this->argument('client_id') ?? 1;

        $project = Project::with(['engagementPartner', 'manager', 'client'])->find($projectId);
        $client = Client::with('user')->find($clientId);

        if (!$project) {
            $this->error("Project {$projectId} not found");
            return;
        }

        if (!$client) {
            $this->error("Client {$clientId} not found");
            return;
        }

        $this->info("=== PROJECT INFO ===");
        $this->line("ID: {$project->id}");
        $this->line("Name: {$project->name}");
        $this->line("Job ID: " . ($project->job_id ?: 'NOT SET'));
        $this->line("Engagement Name: " . ($project->engagement_name ?: 'NOT SET'));
        $this->line("Engagement Partner ID: " . ($project->engagement_partner_id ?: 'NOT SET'));
        $this->line("Manager ID: " . ($project->manager_id ?: 'NOT SET'));

        $this->info("\n=== CLIENT INFO ===");
        $this->line("ID: {$client->id}");
        $this->line("Company: {$client->company_name}");
        $this->line("User ID: {$client->user_id}");
        $this->line("User Name: " . ($client->user ? $client->user->name : 'NO USER'));

        // Get available MTC users
        $this->info("\n=== AVAILABLE MTC STAFF (for Requestor) ===");
        $mtcUsers = User::whereIn('role', [
            User::ROLE_SYSTEM_ADMIN,
            User::ROLE_ENGAGEMENT_PARTNER,
            User::ROLE_MANAGER,
            User::ROLE_ASSOCIATE
        ])->get();

        if ($mtcUsers->isEmpty()) {
            $this->warn("No MTC staff found!");
        } else {
            $this->table(['ID', 'Name', 'Email', 'Role', 'Excel Reference'],
                $mtcUsers->map(function($user) use ($project) {
                    $references = [$user->name, $user->email];

                    if ($project->engagement_partner_id === $user->id) {
                        $references[] = 'EYM';
                        $references[] = 'EP';
                    }

                    if ($project->manager_id === $user->id) {
                        $references[] = 'MNGR 1';
                        $references[] = 'Manager';
                    }

                    if ($user->isAssociate()) {
                        $references[] = 'Staff';
                        $references[] = 'Staff 1';
                    }

                    return [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->getRoleDisplayName(),
                        implode(', ', $references)
                    ];
                })
            );
        }

        // Get available client users
        $this->info("\n=== AVAILABLE CLIENT USERS (for Assigned to) ===");
        if ($client->user) {
            $this->table(['ID', 'Name', 'Email', 'Excel Reference'], [
                [
                    $client->user->id,
                    $client->user->name,
                    $client->user->email,
                    $client->user->name . ', ' . $client->user->email . ', Client, Client Staff 1'
                ]
            ]);
        } else {
            $this->warn("Client has no associated user account!");
        }

        // Show sample Excel data
        $this->info("\n=== SAMPLE EXCEL DATA ===");
        $this->line("Based on your current data, here's what should work:");

        $sampleData = [];

        if ($mtcUsers->isNotEmpty()) {
            $firstStaff = $mtcUsers->first();
            $clientRef = $client->user ? $client->user->name : 'Client';

            $sampleData[] = [
                'CF',
                'Trial Balance as of year-end',
                $firstStaff->name,
                '25/07/2025',
                $clientRef,
                'Pending'
            ];
        }

        $this->table(['Category', 'Request Description', 'Requestor', 'Date Requested', 'Assigned to', 'Status'], $sampleData);

        // Show setup recommendations
        $this->info("\n=== SETUP RECOMMENDATIONS ===");

        if (!$project->engagement_partner_id) {
            $this->warn("⚠ Project has no Engagement Partner assigned");
            $this->line("Fix: Set engagement_partner_id in projects table");
        }

        if (!$project->manager_id) {
            $this->warn("⚠ Project has no Manager assigned");
            $this->line("Fix: Set manager_id in projects table");
        }

        if (!$project->job_id) {
            $this->warn("⚠ Project has no Job ID");
            $this->line("Fix: Job ID will be auto-generated on next save");
        }

        if (!$client->user) {
            $this->error("❌ Client has no user account");
            $this->line("Fix: Create user account for client");
        }

        return 0;
    }
}
