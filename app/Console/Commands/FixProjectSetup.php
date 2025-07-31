<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\User;
use App\Services\JobGenerationService;

class FixProjectSetup extends Command
{
    protected $signature = 'fix:project-setup {project_id?}';
    protected $description = 'Fix project setup for import testing';

    public function handle()
    {
        $projectId = $this->argument('project_id') ?? 1;
        $project = Project::find($projectId);

        if (!$project) {
            $this->error("Project {$projectId} not found");
            return;
        }

        $this->info("Fixing project setup for: {$project->name}");

        // Fix job_id if missing
        if (!$project->job_id && $project->engagement_type) {
            $jobService = app(JobGenerationService::class);
            $project->job_id = $jobService->generateJobId($project->engagement_type);
            $this->line("✓ Generated job_id: {$project->job_id}");
        }

        // Fix engagement_name if missing
        if (!$project->engagement_name) {
            $project->engagement_name = $project->name;
            $this->line("✓ Set engagement_name: {$project->engagement_name}");
        }

        // Assign engagement partner if missing
        if (!$project->engagement_partner_id) {
            $ep = User::where('role', User::ROLE_ENGAGEMENT_PARTNER)->first();
            if ($ep) {
                $project->engagement_partner_id = $ep->id;
                $this->line("✓ Assigned Engagement Partner: {$ep->name}");
            } else {
                $this->warn("⚠ No Engagement Partner found in system");
            }
        }

        // Assign manager if missing
        if (!$project->manager_id) {
            $manager = User::where('role', User::ROLE_MANAGER)->first();
            if ($manager) {
                $project->manager_id = $manager->id;
                $this->line("✓ Assigned Manager: {$manager->name}");
            } else {
                $this->warn("⚠ No Manager found in system");
            }
        }

        // Save changes
        $project->save();

        // Show final status
        $this->info("\n=== UPDATED PROJECT ===");
        $this->table(['Field', 'Value'], [
            ['ID', $project->id],
            ['Job ID', $project->job_id ?: 'NOT SET'],
            ['Name', $project->name],
            ['Engagement Name', $project->engagement_name ?: 'NOT SET'],
            ['Engagement Partner', $project->engagementPartner?->name ?: 'NOT SET'],
            ['Manager', $project->manager?->name ?: 'NOT SET'],
            ['Client', $project->client?->company_name ?: 'NOT SET'],
        ]);

        $this->info("\n✅ Project setup completed!");
        $this->line("You can now test: php artisan test:import-users {$projectId}");

        return 0;
    }
}
