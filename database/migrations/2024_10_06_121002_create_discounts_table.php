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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', );
            $table->text('description')->nullable();
            $table->enum('type', ['fixed', 'percentage', 'buy_x_get_y', 'quantity_based', 'bulk_discount'])->default('percentage');
            $table->decimal('value', 15, 2); // nilai diskon utama
            $table->decimal('min_quantity', 8, 2)->nullable(); // minimal qty untuk diskon
            $table->decimal('max_quantity', 8, 2)->nullable(); // maksimal qty untuk diskon
            $table->decimal('min_amount', 15, 2)->nullable(); // minimal pembelian untuk diskon (rp)
            $table->integer('buy_quantity')->nullable(); // untuk buy X get Y
            $table->integer('get_quantity')->nullable(); // untuk buy X get Y
            $table->json('quantity_tiers')->nullable(); // untuk bulk discount bertingkat
            $table->enum('apply_to', ['all', 'category', 'product'])->default('all');
            $table->json('applicable_items')->nullable(); // category_ids atau product_ids
            $table->enum('customer_type', ['all', 'retail', 'wholesale', 'member'])->default('all');
            $table->boolean('combinable')->default(false); // bisa dikombinasi dengan diskon lain
            $table->integer('usage_limit')->nullable(); // batas penggunaan
            $table->integer('usage_count')->default(0); // sudah digunakan berapa kali
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('start_date')->nullable();
            $table->date('expired_date')->nullable();
            $table->time('start_time')->nullable(); // jam mulai (untuk diskon harian)
            $table->time('end_time')->nullable(); // jam berakhir
            // $table->json('valid_days')->nullable(); // hari berlaku [1,2,3,4,5] (senin-jumat)
            $table->timestamps();
            $table->softDeletes();

            // Index untuk performa
            $table->index(['status', 'start_date', 'expired_date']);
            $table->index(['customer_type', 'status']);
            $table->index(['apply_to', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
