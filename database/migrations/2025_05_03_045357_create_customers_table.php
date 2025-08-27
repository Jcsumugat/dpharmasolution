<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); // Primary key (auto-incrementing integer)
            $table->string('full_name', 100);
            $table->string('address', 255);
            $table->date('birthdate');
            $table->string('sex', 10);
            $table->string('email_address', 50)->unique();
            $table->string('contact_number', 20);
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_restricted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
}