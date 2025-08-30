<?php
// database/migrations/xxxx_xx_xx_create_pos_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->enum('customer_type', ['walk_in', 'regular'])->default('walk_in');
            $table->string('customer_name')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('change_amount', 10, 2);
            $table->enum('payment_method', ['cash', 'card', 'gcash'])->default('cash');
            $table->enum('status', ['completed', 'cancelled', 'refunded'])->default('completed');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('processed_by'); // admin/user ID
            $table->timestamps();

            $table->index(['transaction_id', 'customer_type', 'status']);
        });

        Schema::create('pos_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('pos_transactions')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('product_name'); // Store name at time of sale
            $table->string('brand_name'); // Store brand at time of sale
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Price at time of sale
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pos_transaction_items');
        Schema::dropIfExists('pos_transactions');
    }
};
