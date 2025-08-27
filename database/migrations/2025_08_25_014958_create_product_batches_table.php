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
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('batch_number')->index();
            $table->date('expiration_date')->index();
            $table->integer('quantity_received')->default(0);
            $table->integer('quantity_remaining')->default(0)->index();
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->date('received_date')->index();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['product_id', 'quantity_remaining']); // For available stock queries
            $table->index(['product_id', 'expiration_date']); // For FIFO queries
            $table->index(['expiration_date', 'quantity_remaining']); // For expiration checks
            
            // Unique constraint to prevent duplicate batch numbers per product
            $table->unique(['product_id', 'batch_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};