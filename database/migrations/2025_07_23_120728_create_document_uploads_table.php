<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbc_request_item_id')->constrained()->onDelete('cascade');
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path');
            $table->string('file_extension');
            $table->integer('file_size'); // in bytes
            $table->string('mime_type');
            $table->enum('status', ['uploaded', 'approved', 'rejected'])->default('uploaded');
            $table->text('admin_notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_uploads');
    }
};
