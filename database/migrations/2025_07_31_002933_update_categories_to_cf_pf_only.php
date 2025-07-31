<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->info('Starting category migration to CF/PF...');

        // First, update existing data to CF/PF format
        $this->migrateCategoryData();

        // Then update the schema to use ENUM
        DB::statement("ALTER TABLE pbc_request_items MODIFY COLUMN category ENUM('CF', 'PF') NULL");
        DB::statement("ALTER TABLE pbc_template_items MODIFY COLUMN category ENUM('CF', 'PF') NULL");

        $this->info('Category migration completed!');
    }

    public function down(): void
    {
        // Revert back to varchar
        DB::statement("ALTER TABLE pbc_request_items MODIFY COLUMN category VARCHAR(255) NULL");
        DB::statement("ALTER TABLE pbc_template_items MODIFY COLUMN category VARCHAR(255) NULL");
    }

    private function migrateCategoryData(): void
    {
        $this->info('Migrating existing category data...');

        // Clear any test data and set proper categories
        DB::table('pbc_request_items')->whereNotNull('category')->update(['category' => 'CF']);
        DB::table('pbc_template_items')->whereNotNull('category')->update(['category' => 'CF']);

        $this->info('Category data migration completed.');
    }

    private function info($message): void
    {
        if (app()->runningInConsole()) {
            echo $message . "\n";
        }
    }
};
