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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('po_number')->unique();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('received_date')->nullable();
            $table->enum('status', ['pending', 'ordered', 'partially_received', 'received', 'cancelled'])->default('pending');
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index untuk performa
            $table->index('supplier_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('order_date');
            $table->index('expected_delivery_date');
            $table->index(['supplier_id', 'status']);
            $table->index(['order_date', 'status']);
            $table->index(['expected_delivery_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
