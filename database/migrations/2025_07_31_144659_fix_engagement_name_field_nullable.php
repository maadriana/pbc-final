<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Make engagement_name nullable to fix the SQL error
            $table->string('engagement_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Revert back to not nullable (but this might cause issues if there are null values)
            $table->string('engagement_name')->nullable(false)->change();
        });
    }
};
