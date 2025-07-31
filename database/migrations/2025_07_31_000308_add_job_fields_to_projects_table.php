<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add job_id field with unique constraint
            $table->string('job_id', 20)->unique()->after('id');

            // Add engagement_name (separate from project name for wireframe)
            $table->string('engagement_name')->after('name');

            // Add direct references to engagement partner and manager
            // (These will work alongside the project_assignments table)
            $table->foreignId('engagement_partner_id')->nullable()->after('engagement_type')->constrained('users')->onDelete('set null');
            $table->foreignId('manager_id')->nullable()->after('engagement_partner_id')->constrained('users')->onDelete('set null');

            // Add engagement period fields (if not already exist)
            // Note: These might already exist from your earlier migration
            if (!Schema::hasColumn('projects', 'engagement_period_start')) {
                $table->date('engagement_period_start')->nullable()->after('manager_id');
            }
            if (!Schema::hasColumn('projects', 'engagement_period_end')) {
                $table->date('engagement_period_end')->nullable()->after('engagement_period_start');
            }

            // Add indexes for better performance
            $table->index(['job_id']);
            $table->index(['engagement_type', 'status']);
            $table->index(['engagement_partner_id']);
            $table->index(['manager_id']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['engagement_partner_id']);
            $table->dropForeign(['manager_id']);

            // Drop indexes
            $table->dropIndex(['job_id']);
            $table->dropIndex(['engagement_type', 'status']);
            $table->dropIndex(['engagement_partner_id']);
            $table->dropIndex(['manager_id']);

            // Drop columns
            $table->dropColumn([
                'job_id',
                'engagement_name',
                'engagement_partner_id',
                'manager_id'
            ]);
        });
    }
};
