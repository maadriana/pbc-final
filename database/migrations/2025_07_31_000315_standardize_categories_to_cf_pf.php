<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, let's migrate existing category data to CF/PF format
        $this->migrateCategoryData();

        // Then update the schema to use ENUM
        Schema::table('pbc_request_items', function (Blueprint $table) {
            $table->enum('category', ['CF', 'PF'])->nullable()->change();
        });

        Schema::table('pbc_template_items', function (Blueprint $table) {
            $table->enum('category', ['CF', 'PF'])->nullable()->change();
        });
    }

    public function down(): void
    {
        // Revert back to flexible string categories
        Schema::table('pbc_request_items', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
        });

        Schema::table('pbc_template_items', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
        });
    }

    private function migrateCategoryData(): void
    {
        // Mapping rules for existing categories to CF/PF
        $categoryMappings = [
            // Current File mappings
            'Current File' => 'CF',
            'current file' => 'CF',
            'Financial' => 'CF',
            'financial' => 'CF',
            'Trial Balance' => 'CF',
            'Bank' => 'CF',
            'Receivables' => 'CF',
            'Payables' => 'CF',
            'Inventory' => 'CF',
            'Fixed Assets' => 'CF',
            'Expenses' => 'CF',
            'Revenue' => 'CF',

            // Permanent File mappings
            'Permanent File' => 'PF',
            'permanent file' => 'PF',
            'Legal' => 'PF',
            'legal' => 'PF',
            'Articles' => 'PF',
            'Incorporation' => 'PF',
            'By-laws' => 'PF',
            'Registration' => 'PF',
            'Minutes' => 'PF',
            'Contracts' => 'PF',
            'Tax' => 'PF',
            'Statutory' => 'PF',
        ];

        // Update pbc_request_items
        foreach ($categoryMappings as $oldCategory => $newCategory) {
            DB::table('pbc_request_items')
                ->where('category', 'LIKE', "%{$oldCategory}%")
                ->update(['category' => $newCategory]);
        }

        // Update pbc_template_items
        foreach ($categoryMappings as $oldCategory => $newCategory) {
            DB::table('pbc_template_items')
                ->where('category', 'LIKE', "%{$oldCategory}%")
                ->update(['category' => $newCategory]);
        }

        // Set any remaining unmapped categories to CF (Current File) as default
        DB::table('pbc_request_items')
            ->whereNotIn('category', ['CF', 'PF'])
            ->whereNotNull('category')
            ->update(['category' => 'CF']);

        DB::table('pbc_template_items')
            ->whereNotIn('category', ['CF', 'PF'])
            ->whereNotNull('category')
            ->update(['category' => 'CF']);
    }
};
