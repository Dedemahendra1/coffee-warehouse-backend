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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->string('thumbnail');
            $table->text('about');

            // Satuan barang (kg, gram, liter, ml, pcs, box, dll)
            $table->string('unit', 20);

            // Harga per satuan
            $table->unsignedInteger('price');

            $table->foreignId('category_id')
                ->constrained()
                ->onDelete('cascade');

            $table->boolean('is_popular')->default(false);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};