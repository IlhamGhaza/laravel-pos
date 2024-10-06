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
            $table->string('payment_amount')->after('transaction_time');
            $table->integer('sub_total')->after('payment_amount');
            $table->integer('tax')->after('sub_total');
            $table->integer('discount')->after('tax');
            $table->integer('service_charge')->after('discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
            $table->dropColumn('payment_amount');
            $table->dropColumn('sub_total');
            $table->dropColumn('tax');
            $table->dropColumn('discount');
            $table->dropColumn('service_charge');
            $table->dropColumn('total');
        });
    }
};
