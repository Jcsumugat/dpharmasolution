<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->default('staff');
                $table->rememberToken();
                $table->timestamps();

                $table->index('role');
            });
        }

        try {
            $existingAdmin = DB::table('users')
                ->where('email', 'mjspharmacy@gmail.com')
                ->first();

            if (!$existingAdmin) {
                DB::table('users')->insert([
                    'name' => 'MJS Pharmacy Admin',
                    'email' => 'mjspharmacy@gmail.com',
                    'password' => Hash::make('dpharmasolution'),
                    'role' => 'admin',
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to insert admin user during migration: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
