<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin user already exists
        $adminExists = DB::table('users')
            ->where('email', 'mjspharmacy@gmail.com')
            ->exists();

        if (!$adminExists) {
            DB::table('users')->insert([
                'name' => 'Mirriam Joy E. Barrientos',
                'email' => 'mjspharmacy@gmail.com',
                'password' => Hash::make('dpharmasolution'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}