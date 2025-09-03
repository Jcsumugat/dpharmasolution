// database/migrations/xxxx_create_prescription_messages_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrescriptionMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('prescription_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->enum('sender_type', ['admin', 'customer']);
            $table->foreignId('sender_id')->nullable(); // admin_id or customer_id
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('prescription_messages');
    }
}
