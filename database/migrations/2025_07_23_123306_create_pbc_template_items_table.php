<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbc_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbc_template_id')->constrained()->onDelete('cascade');
            $table->string('category')->nullable();
            $table->text('particulars');
            $table->boolean('is_required')->default(true);
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_template_items');
    }
};
