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
            $table->id();
            $table->string('name');
            //description
            $table->text('description')->nullable();
            //price
            $table->integer('price')->default(0);
            //stock
            $table->integer('stock')->default(0);
            //category enum (food, drink, snack)
            $table->foreignId('category_id')->constrained('categories');
            //image
            $table->string('image')->nullable();
            $table->boolean('is_best_seller')->default(false);
            $table->timestamps();
            $table->softDeletes();
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
