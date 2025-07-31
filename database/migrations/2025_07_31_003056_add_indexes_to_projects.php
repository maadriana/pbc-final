<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add indexes for better performance (with error handling)
            try {
                if (!$this->indexExists('projects', 'projects_job_id_index')) {
                    $table->index(['job_id'], 'projects_job_id_index');
                }
            } catch (\Exception $e) {
                // Index might already exist
            }

            try {
                if (!$this->indexExists('projects', 'projects_engagement_type_status_index')) {
                    $table->index(['engagement_type', 'status'], 'projects_engagement_type_status_index');
                }
            } catch (\Exception $e) {
                // Index might already exist
            }

            try {
                if (!$this->indexExists('projects', 'projects_engagement_partner_id_index')) {
                    $table->index(['engagement_partner_id'], 'projects_engagement_partner_id_index');
                }
            } catch (\Exception $e) {
                // Index might already exist
            }

            try {
                if (!$this->indexExists('projects', 'projects_manager_id_index')) {
                    $table->index(['manager_id'], 'projects_manager_id_index');
                }
            } catch (\Exception $e) {
                // Index might already exist
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            try {
                $table->dropIndex('projects_job_id_index');
            } catch (\Exception $e) {}

            try {
                $table->dropIndex('projects_engagement_type_status_index');
            } catch (\Exception $e) {}

            try {
                $table->dropIndex('projects_engagement_partner_id_index');
            } catch (\Exception $e) {}

            try {
                $table->dropIndex('projects_manager_id_index');
            } catch (\Exception $e) {}
        });
    }

    private function indexExists($table, $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
};
