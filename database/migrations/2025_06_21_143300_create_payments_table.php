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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method');
            $table->string('status');
            $table->string('transaction_id')->nullable();
            $table->json('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('transaction_id');
            $table->index('status');
            $table->index('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
