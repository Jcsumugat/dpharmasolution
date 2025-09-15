<?php
// 2024_01_01_000006_create_user_online_status_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_online_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen')->useCurrent();
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('admin_id');
            $table->index('is_online');
            $table->index('last_seen');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_online_status');
    }
};
