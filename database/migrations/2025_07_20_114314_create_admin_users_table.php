<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // If you don't have an admins table, create it
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('admin'); // admin, super_admin, etc.
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        // OR if you want to add role to existing users table
        // Schema::table('users', function (Blueprint $table) {
        //     $table->string('role')->default('user')->after('email'); // user, admin, super_admin
        //     $table->boolean('is_admin')->default(false)->after('role');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
        
        // OR if you added columns to users table
        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropColumn(['role', 'is_admin']);
        // });
    }
};