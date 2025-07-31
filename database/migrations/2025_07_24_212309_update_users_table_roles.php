<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, modify the role column to allow the new values
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('system_admin', 'engagement_partner', 'manager', 'associate', 'client') NOT NULL DEFAULT 'client'");

        // Optionally, update existing 'admin' roles to 'system_admin' if you had them
        DB::table('users')
            ->where('role', 'admin')
            ->update(['role' => 'system_admin']);
    }

    public function down(): void
    {
        // Revert back to original roles if needed
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'client') NOT NULL DEFAULT 'client'");
    }
};
