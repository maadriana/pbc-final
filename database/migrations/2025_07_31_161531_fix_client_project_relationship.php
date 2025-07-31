<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Migrate data from project_clients to projects.client_id if needed
        $this->migrateProjectClientData();

        // Step 2: Drop the many-to-many relationship table
        Schema::dropIfExists('project_clients');

        // Step 3: Ensure projects.client_id is properly set up
        Schema::table('projects', function (Blueprint $table) {
            // Make sure client_id exists and is properly constrained
            if (!Schema::hasColumn('projects', 'client_id')) {
                $table->foreignId('client_id')->nullable()->after('description')
                      ->constrained()->onDelete('set null');
            }

            // Add index for better performance
            if (!$this->indexExists('projects', 'projects_client_id_index')) {
                $table->index(['client_id'], 'projects_client_id_index');
            }
        });
    }

    public function down(): void
    {
        // Recreate the many-to-many table
        Schema::create('project_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
        });

        // Migrate data back from projects.client_id to project_clients
        $this->migrateBackToProjectClients();
    }

    private function migrateProjectClientData(): void
    {
        // Check if we have data in project_clients to migrate
        if (Schema::hasTable('project_clients')) {
            $projectClients = DB::table('project_clients')->get();

            foreach ($projectClients as $pc) {
                DB::table('projects')
                    ->where('id', $pc->project_id)
                    ->update(['client_id' => $pc->client_id]);
            }

            echo "Migrated " . $projectClients->count() . " project-client relationships.\n";
        }
    }

    private function migrateBackToProjectClients(): void
    {
        $projects = DB::table('projects')->whereNotNull('client_id')->get();

        foreach ($projects as $project) {
            DB::table('project_clients')->insert([
                'project_id' => $project->id,
                'client_id' => $project->client_id,
                'assigned_by' => $project->created_by,
                'assigned_at' => $project->created_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function indexExists($table, $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
};
