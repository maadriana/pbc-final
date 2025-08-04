<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add the missing fields that are needed for the edit form
            $table->string('job_id')->nullable()->unique()->after('id');
            $table->string('engagement_type')->nullable()->after('description');
            $table->foreignId('client_id')->nullable()->constrained('clients')->after('engagement_type');
            $table->date('engagement_period_start')->nullable()->after('end_date');
            $table->date('engagement_period_end')->nullable()->after('engagement_period_start');

            // Update status enum to include cancelled
            $table->enum('status', ['active', 'completed', 'on_hold', 'cancelled'])->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'job_id',
                'engagement_type',
                'client_id',
                'engagement_period_start',
                'engagement_period_end'
            ]);

            // Revert status enum
            $table->enum('status', ['active', 'completed', 'on_hold'])->default('active')->change();
        });
    }
};
