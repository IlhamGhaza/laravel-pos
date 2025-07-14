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
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained('order_items');
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('type', [
                'sale',
                'restock',
                'adjustment_in',
                'adjustment_out',
                'sale_return',
                'sale_adjustment',
                'spoilage',
                'expired',
                'damaged',
                'lost',
                'theft',
                'sample',
                'waste',
                'initial_stock',
                'transfer_in',
                'transfer_out',
                'production',
                'quality_reject'
            ]);
            $table->decimal('quantity_change', 10, 2);
            $table->decimal('stock_before_change', 10, 2)->nullable();
            $table->decimal('stock_after_change', 10, 2)->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();


            $table->index('product_id');
            $table->index('order_item_id');
            $table->index('purchase_order_item_id');
            $table->index('user_id');
            $table->index('type');
            $table->index(['product_id', 'type']);
            $table->index(['product_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};
