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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->decimal('quantity_ordered', 10, 2);
            $table->decimal('quantity_received', 10, 2)->default(0);
            $table->decimal('unit_cost_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
            $table->softDeletes();

            // Index untuk performa
            $table->index('purchase_order_id');
            $table->index('product_id');
            $table->index(['purchase_order_id', 'product_id']);
            $table->index(['product_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
