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
        Schema::table('orders', function (Blueprint $table) {
            // == Bagian Informasi Utama & Relasi ==
            $table->foreignId('customer_id')->nullable()->after('kasir_id')->constrained('customers')->onDelete('set null');
            $table->string('kasir_name')->nullable()->after('kasir_id')->comment('Snapshot nama kasir saat transaksi');
            $table->string('customer_name')->nullable()->after('customer_id');

            // == Bagian Detail & Status Order ==
            $table->string('status', 50)->default('pending')->after('total_item');
            $table->enum('order_type', ['in-person', 'phone', 'mobile_app'])->default('in-person')->after('status');
            $table->text('customer_order_notes')->nullable()->after('order_type');

            // == Bagian Detail Pajak & Service Charge (Snapshot) ==
            // Kolom ini sudah ada dari migrasi sebelumnya, kita pastikan posisinya rapi.
            // $table->decimal('tax_amount', 15, 2)->default(0);
            // $table->foreignId('tax_id')->nullable()->constrained('taxes')->onDelete('set null');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_amount');
            // $table->decimal('service_charge', 15, 2)->default(0);
            // $table->foreignId('service_charge_id')->nullable()->constrained('service_charges')->onDelete('set null');
            $table->decimal('service_charge_rate', 5, 2)->default(0)->after('service_charge');

            // == Bagian Detail Diskon (Snapshot) ==
            // Kolom ini sudah ada dari migrasi sebelumnya, kita pastikan posisinya rapi.
            // $table->decimal('discount_amount', 15, 2)->default(0);
            $table->foreignId('discount_id')->nullable()->after('discount_amount')->constrained('discounts')->onDelete('set null');
            $table->json('discount_details')->nullable()->after('discount_id');

            // == Bagian Pembayaran ==
            $table->decimal('payment_amount', 15, 2)->nullable()->after('total_price');
            $table->decimal('change_amount', 15, 2)->nullable()->after('payment_amount');
            $table->timestamp('paid_at')->nullable()->after('change_amount');

            // Midtrans integration
            $table->string('midtrans_transaction_id')->nullable()->index()->after('paid_at');
            $table->string('midtrans_order_id')->nullable()->index()->after('midtrans_transaction_id');
            $table->json('payment_gateway_response')->nullable()->after('midtrans_order_id');

            // Mobile sync
            $table->boolean('is_synced_from_mobile')->default(false)->after('payment_gateway_response');
            $table->enum('mobile_sync_validation_status', ['pending_validation', 'validated_ok', 'validation_failed', 'requires_review'])->nullable()->after('is_synced_from_mobile');
            $table->text('mobile_sync_notes')->nullable()->after('mobile_sync_validation_status');
            $table->timestamp('mobile_synced_at')->nullable()->after('mobile_sync_notes');

            // Indexes
            $table->index('order_type');
            $table->index('paid_at');
            $table->index('is_synced_from_mobile');
            $table->index('mobile_sync_validation_status');
            $table->index(['status', 'paid_at']);
            $table->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign keys first to avoid errors
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['discount_id']);

            // Drop indexes
            $table->dropIndex('orders_order_type_index');
            $table->dropIndex('orders_paid_at_index');
            $table->dropIndex('orders_is_synced_from_mobile_index');
            $table->dropIndex('orders_mobile_sync_validation_status_index');
            $table->dropIndex('orders_status_paid_at_index');
            $table->dropIndex('orders_customer_id_status_index');
            $table->dropIndex('orders_midtrans_transaction_id_index');
            $table->dropIndex('orders_midtrans_order_id_index');

            // Drop columns
            $table->dropColumn([
                'status',
                'kasir_name',
                'customer_id',
                'customer_name',
                'order_type',
                'customer_order_notes',
                // 'tax_id',
                'tax_rate',
                // 'service_charge_id',
                'service_charge_rate',
                'discount_id',
                'discount_details',
                'payment_amount',
                'change_amount',
                'paid_at',
                'midtrans_transaction_id',
                'midtrans_order_id',
                'payment_gateway_response',
                'is_synced_from_mobile',
                'mobile_sync_validation_status',
                'mobile_sync_notes',
                'mobile_synced_at'
            ]);
        });
    }
};
