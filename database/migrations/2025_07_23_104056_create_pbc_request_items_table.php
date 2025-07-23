<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbc_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbc_request_id')->constrained()->onDelete('cascade');
            $table->string('category')->nullable(); // e.g., "Permanent File", "Current File"
            $table->text('particulars'); // Description of required document
            $table->date('date_requested')->nullable();
            $table->boolean('is_required')->default(true);
            $table->enum('status', ['pending', 'uploaded', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->integer('order_index')->default(0);
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_request_items');
    }
};
