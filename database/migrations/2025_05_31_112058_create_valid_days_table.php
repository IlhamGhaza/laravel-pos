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
        Schema::create('valid_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 1=Senin, 2=Selasa, ..., 7=Minggu
            $table->timestamps();
            $table->softDeletes();

            // Index untuk performa
            $table->index(['discount_id', 'day_of_week']);
            $table->unique(['discount_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valid_days');
    }
};
