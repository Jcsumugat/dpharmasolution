<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    public function up()
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('product_code', 50)->unique();
        $table->string('product_name', 100);
        $table->string('manufacturer', 100);
        $table->string('product_type', 50);
        $table->string('form_type', 50);
        $table->string('dosage_unit', 20);
        $table->string('packaging_unit', 20);
        $table->decimal('unit_price', 10, 2);
        $table->decimal('sale_price', 10, 2);
        $table->boolean('classification')->default(false);
        $table->unsignedInteger('stock_quantity');
        $table->integer('reorder_level')->nullable();
        $table->date('expiration_date');
        $table->string('batch_number')->nullable();
        $table->unsignedBigInteger('category_id')->nullable();
        $table->unsignedBigInteger('supplier_id');
        $table->string('brand_name')->nullable();
        $table->timestamp('notification_sent_at')->nullable();
        $table->timestamps();

        // Foreign keys and indexes
        $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
        $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        $table->index('expiration_date');
        $table->index('stock_quantity');
    });
}


    public function down()
    {
        Schema::dropIfExists('products');
    }
}
