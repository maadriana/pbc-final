<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_uploads', function (Blueprint $table) {
            // Make file_extension nullable or give it a default value
            $table->string('file_extension')->nullable()->default('')->change();
        });

        // Optional: Update existing records to populate file_extension from original_filename
        DB::statement("
            UPDATE document_uploads
            SET file_extension = LOWER(SUBSTR(original_filename, INSTR(original_filename, '.') + 1))
            WHERE original_filename LIKE '%.%' AND (file_extension IS NULL OR file_extension = '')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_uploads', function (Blueprint $table) {
            // Revert file_extension to not nullable without default
            $table->string('file_extension')->nullable(false)->default(null)->change();
        });
    }
};
