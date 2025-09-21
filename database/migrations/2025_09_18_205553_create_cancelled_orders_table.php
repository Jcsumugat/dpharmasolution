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
        Schema::create('cancelled_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->text('reason');
            $table->string('cancelled_by')->nullable(); // admin username or 'customer'
            $table->timestamps();

            // Index for faster lookups
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancelled_orders');
    }
};
