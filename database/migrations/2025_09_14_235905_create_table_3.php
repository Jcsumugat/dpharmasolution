<?php
// 2024_01_01_000003_create_chat_messages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->text('message');
            $table->enum('message_type', ['text', 'file', 'image', 'system'])->default('text');
            $table->boolean('is_from_customer')->default(true);
            $table->boolean('is_internal_note')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('customer_id');
            $table->index('admin_id');
            $table->index('created_at');
            $table->index('is_from_customer');

            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
};
