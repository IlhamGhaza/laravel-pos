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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('tracking_number')->nullable();

            // Delivery address info
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->text('recipient_address');
            $table->string('recipient_city');
            $table->string('recipient_state');
            $table->string('recipient_postal_code');

            // Delivery timing
            $table->timestamp('scheduled_delivery_datetime')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivery_at')->nullable();

            // Status & tracking
            $table->enum('status', ['pending', 'scheduled', 'dispatched', 'in_transit', 'delivered', 'returned', 'failed', 'cancelled'])->default('pending');
            $table->string('proof_of_delivery_image_path')->nullable();

            // Pupuk specific
            $table->decimal('total_weight', 10, 2)->nullable();
            $table->boolean('requires_special_handling')->default(false);

            // Notes
            $table->text('delivery_notes_internal')->nullable();
            $table->text('delivery_notes_customer')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('tracking_number');
            $table->index('status');
            $table->index('scheduled_delivery_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
