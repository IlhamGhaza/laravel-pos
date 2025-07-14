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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->timestamp('transaction_time');
            $table->foreignId('kasir_id')->constrained('users');

            $table->decimal('sub_total', 15, 2); // Subtotal sebelum tax & service charge
            $table->decimal('tax_amount', 15, 2)->default(0); // Jumlah pajak
            $table->decimal('service_charge', 15, 2)->default(0); // Biaya layanan
            $table->decimal('discount_amount', 15, 2)->default(0); // Jumlah diskon
            $table->decimal('total_price', 15, 2); // Total akhir
            $table->integer('total_item');
            $table->enum('payment_method', ['cash', 'card', 'transfer', 'qris', 'ewallet'])->default('cash');

            $table->timestamps();
            $table->softDeletes();

            $table->index('kasir_id');
            $table->index('transaction_time');
            $table->index('payment_method');
            $table->index('created_at');
            $table->index('total_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
