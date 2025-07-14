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
            // Add tax rate column
            // $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_amount');

            // Add service charge rate column
            // $table->decimal('service_charge_rate', 5, 2)->default(0)->after('service_charge');

            // Add discount details as JSON
            // $table->json('discount_details')->nullable()->after('discount_amount');

            // Add foreign key constraints (soft delete aware)
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->onDelete('set null');
            $table->foreignId('service_charge_id')->nullable()->constrained('service_charges')->onDelete('set null');
            // $table->foreignId('discount_id')->nullable()->constrained('discounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);
            $table->dropForeign(['service_charge_id']);
            $table->dropColumn([
                'tax_id',
                'service_charge_id',
            ]);
        });
    }
};
