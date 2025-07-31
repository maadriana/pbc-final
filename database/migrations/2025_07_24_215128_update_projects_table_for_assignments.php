<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('description')->constrained()->onDelete('set null');
            $table->enum('engagement_type', ['audit', 'accounting', 'tax', 'special_engagement', 'others'])->after('client_id');
            $table->date('engagement_period_start')->nullable()->after('engagement_type');
            $table->date('engagement_period_end')->nullable()->after('engagement_period_start');
            $table->json('contact_persons')->nullable()->after('engagement_period_end');

            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn([
                'client_id',
                'engagement_type',
                'engagement_period_start',
                'engagement_period_end',
                'contact_persons'
            ]);
        });
    }
};
