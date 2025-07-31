<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CheckDatabaseStructure extends Command
{
    protected $signature = 'db:check-structure';
    protected $description = 'Check current database structure for PBC system';

    public function handle()
    {
        $this->info('Checking current database structure...');

        // Check projects table
        $this->line('=== PROJECTS TABLE ===');
        if (Schema::hasTable('projects')) {
            $columns = DB::select('DESCRIBE projects');
            foreach ($columns as $column) {
                $this->line("✓ {$column->Field} ({$column->Type})");
            }
        } else {
            $this->error('❌ Projects table does not exist');
        }

        $this->line('');

        // Check what's missing for wireframe alignment
        $this->line('=== MISSING FIELDS FOR WIREFRAME ===');

        $requiredFields = [
            'job_id' => 'VARCHAR(20) - Auto-generated job IDs (1-01-001)',
            'engagement_name' => 'VARCHAR(255) - Separate from project name',
            'engagement_partner_id' => 'BIGINT - Direct reference to partner',
            'manager_id' => 'BIGINT - Direct reference to manager'
        ];

        foreach ($requiredFields as $field => $description) {
            if (Schema::hasColumn('projects', $field)) {
                $this->info("✅ {$field} - EXISTS");
            } else {
                $this->warn("❌ {$field} - MISSING ({$description})");
            }
        }

        $this->line('');

        // Check pbc_request_items and pbc_template_items categories
        $this->line('=== CATEGORY STRUCTURE ===');

        if (Schema::hasTable('pbc_request_items')) {
            $this->checkCategoryStructure('pbc_request_items');
        }

        if (Schema::hasTable('pbc_template_items')) {
            $this->checkCategoryStructure('pbc_template_items');
        }

        return 0;
    }

    private function checkCategoryStructure($tableName)
    {
        $this->line("--- {$tableName} ---");

        // Get column info
        $columns = collect(DB::select("DESCRIBE {$tableName}"))
            ->keyBy('Field');

        if (isset($columns['category'])) {
            $categoryColumn = $columns['category'];
            $this->line("Category column type: {$categoryColumn->Type}");

            // Check if it's already ENUM('CF','PF')
            if (str_contains($categoryColumn->Type, "enum('CF','PF')") ||
                str_contains($categoryColumn->Type, "enum('PF','CF')")) {
                $this->info("✅ Category is already set to CF/PF enum");
            } else {
                $this->warn("❌ Category needs to be updated to CF/PF enum");

                // Show current category values
                $categories = DB::table($tableName)
                    ->select('category')
                    ->distinct()
                    ->whereNotNull('category')
                    ->pluck('category');

                $this->line("Current category values: " . $categories->implode(', '));
            }
        } else {
            $this->error("❌ No category column found in {$tableName}");
        }
    }
}
