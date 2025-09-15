<?php
// 2024_01_01_000002_create_conversations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('title')->nullable();
            $table->enum('type', ['prescription_inquiry', 'order_concern', 'general_support', 'complaint', 'product_inquiry']);
            $table->enum('status', ['active', 'resolved', 'closed', 'pending'])->default('active');
            $table->enum('priority', ['normal', 'high', 'urgent'])->default('normal');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('admin_id');
            $table->index('status');
            $table->index('priority');
            $table->index('last_message_at');

            $table->foreign('customer_id')->references('customer_id')->on('customers_chat')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('conversations');
    }
};
