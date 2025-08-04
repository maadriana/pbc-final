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
            $table->timestamp('file_deleted_at')->nullable()->after('approved_at');
            $table->unsignedBigInteger('file_deleted_by')->nullable()->after('file_deleted_at');

            // Add foreign key constraint for file_deleted_by
            $table->foreign('file_deleted_by')->references('id')->on('users')->onDelete('set null');

            // Add index for performance when querying deleted files
            $table->index(['file_deleted_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_uploads', function (Blueprint $table) {
            $table->dropForeign(['file_deleted_by']);
            $table->dropIndex(['file_deleted_at', 'status']);
            $table->dropColumn(['file_deleted_at', 'file_deleted_by']);
        });
    }
};
