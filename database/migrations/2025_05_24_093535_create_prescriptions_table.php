<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            
            // Foreign key to customers table (using the primary key 'id')
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            
            // Keep user_id for backward compatibility if needed
            $table->foreignId('user_id')->nullable()->constrained('customers')->onDelete('cascade');
            
            $table->string('mobile_number');
            $table->text('notes')->nullable();
            $table->string('file_path');
            $table->string('token')->unique();
            $table->enum('status', ['pending', 'approved', 'partially_approved', 'declined'])->default('pending');
            $table->string('qr_code_path')->nullable(); // Add this missing column
            $table->text('admin_message')->nullable(); // Add this for admin responses
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};