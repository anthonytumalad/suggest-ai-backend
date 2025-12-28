<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProductionUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Anthony Tumalad',
                'email' => 'anthony@example.com',
                'password_env' => 'ANTHONY_PASSWORD',
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@lewiscollege.edu.ph',
                'password_env' => 'ADMIN_PASSWORD',
            ],
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password_env' => 'JOHN_PASSWORD',
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@thelewiscollege.edu.ph',
                'password_env' => 'ADMINUSER_PASSWORD',
            ],
        ];

        foreach ($users as $userData) {
            $password = env($userData['password_env'], 'DefaultTempPassword123'); 

            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($password),
                ]
            );
        }
    }
}
