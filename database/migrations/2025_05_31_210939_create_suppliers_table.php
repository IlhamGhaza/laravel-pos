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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('company_name')->nullable();
            $table->string('tax_number')->nullable();
            $table->enum('payment_terms', ['cash', 'credit'])->default('cash');
            $table->integer('credit_days')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();


            $table->index('name');
            $table->index('phone_number');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
