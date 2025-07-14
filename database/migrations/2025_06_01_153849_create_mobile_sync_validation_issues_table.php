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
        Schema::create('mobile_sync_validation_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->string('field_name')->nullable();
            $table->text('issue_description');
            $table->text('mobile_value')->nullable();
            $table->text('server_calculated_value')->nullable();
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            //index
            $table->index('order_id');
            $table->index('field_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_sync_validation_issues');
    }
};
