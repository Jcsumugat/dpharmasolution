<?php
// 2024_01_01_000004_create_message_attachments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->string('file_name');
            $table->string('file_path', 500);
            $table->integer('file_size');
            $table->string('file_type');
            $table->string('mime_type');
            $table->timestamps();

            $table->index('message_id');

            $table->foreign('message_id')->references('id')->on('chat_messages')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('message_attachments');
    }
};
