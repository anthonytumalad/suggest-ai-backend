<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->insert([
            [
                'name'              => 'Anthony Tumalad',
                'email'             => 'anthony@example.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('admin123'),
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'name'              => 'Admin',
                'email'             => 'admin@lewiscollege.edu.ph',
                'email_verified_at' => now(),
                'password'          => Hash::make('admin123'),
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'name'              => 'John Doe',
                'email'             => 'john@example.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->whereIn('email', [
                'anthony@example.com',
                'admin@lewiscollege.edu.ph',
                'john@example.com',
            ])
            ->delete();
    }
};