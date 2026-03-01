<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Alice Johnson', 'email' => 'alice@example.com', 'password' => Hash::make('Password@123'), 'is_active' => true],
            ['name' => 'Bob Smith', 'email' => 'bob@example.com', 'password' => Hash::make('Password@123'), 'is_active' => true],
            ['name' => 'Charlie Brown', 'email' => 'charlie@example.com', 'password' => Hash::make('Password@123'), 'is_active' => true],
            ['name' => 'Diana Prince', 'email' => 'diana@example.com', 'password' => Hash::make('Password@123'), 'is_active' => true],
            ['name' => 'Eve Wilson', 'email' => 'eve@example.com', 'password' => Hash::make('Password@123'), 'is_active' => false],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(['email' => $userData['email']], $userData);
        }
    }
}
