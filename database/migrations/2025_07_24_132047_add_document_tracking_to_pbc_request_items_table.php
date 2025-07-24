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
        Schema::table('pbc_request_items', function (Blueprint $table) {
            // Add foreign key to track which document was approved for this item
            $table->foreignId('approved_document_id')
                  ->nullable()
                  ->after('reviewed_by')
                  ->constrained('document_uploads')
                  ->onDelete('set null');

            // Add index for better performance
            $table->index(['status', 'pbc_request_id']);
            $table->index(['reviewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbc_request_items', function (Blueprint $table) {
            $table->dropForeign(['approved_document_id']);
            $table->dropIndex(['status', 'pbc_request_id']);
            $table->dropIndex(['reviewed_at']);
            $table->dropColumn('approved_document_id');
        });
    }
};
