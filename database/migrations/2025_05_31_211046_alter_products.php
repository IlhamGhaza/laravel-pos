<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->after('name');
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('restrict'); // Anda bisa mengubah onDelete behavior jika diperlukan (misalnya, set null, cascade)
            $table->dropColumn('category');
            $table->string('sku')->unique()->nullable()->after('category_id');
            $table->string('unit_of_measure')->nullable()->comment('e.g., kg, liter, sack, 50kg_bag')->after('price');
            $table->date('expired_date')->nullable()->after('unit_of_measure');

            //index
            $table->index('category_id');
            $table->index('sku');
            $table->index('unit_of_measure');
            $table->index('expired_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('sku')->after('description');
            $table->dropColumn('unit_of_measure');
            $table->dropColumn('expired_date');

            //drop index
            $table->dropIndex('category_id');
            $table->dropIndex('sku');
            $table->dropIndex('unit_of_measure');
        });
    }
};
