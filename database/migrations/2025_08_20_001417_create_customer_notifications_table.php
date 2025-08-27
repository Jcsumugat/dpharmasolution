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
        Schema::create('customer_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('prescription_id')->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('general'); // order_received, order_approved, order_ready, etc.
            $table->boolean('is_read')->default(false);
            $table->json('data')->nullable(); // Additional data like prescription details, amounts, etc.
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('prescription_id')->references('id')->on('prescriptions')->onDelete('set null');
            
            $table->index(['customer_id', 'is_read']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_notifications');
    }
};