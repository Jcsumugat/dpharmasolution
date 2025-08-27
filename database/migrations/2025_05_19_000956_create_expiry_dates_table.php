<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpiryDatesTable extends Migration
{
    public function up()
    {
        Schema::create('expiry_dates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique(); // one expiry date per product
            $table->date('expiry_date');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('expiry_dates');
    }
}
