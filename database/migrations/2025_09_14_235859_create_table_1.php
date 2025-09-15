<?php

// 2024_01_01_000001_create_customers_chat_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customers_chat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->unique();
            $table->string('email_address');
            $table->string('full_name');
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_active')->nullable();
            $table->enum('chat_status', ['available', 'busy', 'away', 'offline'])->default('offline');
            $table->timestamps();

            $table->index('customer_id');
            $table->index('email_address');
            $table->index('chat_status');
            $table->index('last_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers_chat');
    }
};
