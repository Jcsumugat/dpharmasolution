<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('stock_movements', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
        $table->string('type'); // 'purchase', 'sale', 'adjustment', 'return'
        $table->integer('quantity'); // positive for in, negative for out
        $table->string('reference_type')->nullable(); // 'sale', 'purchase', 'adjustment'
        $table->unsignedBigInteger('reference_id')->nullable(); // ID of the related record
        $table->text('notes')->nullable();
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
