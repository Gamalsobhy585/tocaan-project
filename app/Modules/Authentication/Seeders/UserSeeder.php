<?php

namespace App\Modules\Authentication\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = [
            [
                'name'              => 'Admin User',
                'email'             => 'admin@example.com',
                'password'          => Hash::make('123456'),
                'email_verified_at' => now(),
            ],
            [
                'name'              => 'Test User',
                'email'             => 'test@example.com',
                'password'          => Hash::make('123456'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}