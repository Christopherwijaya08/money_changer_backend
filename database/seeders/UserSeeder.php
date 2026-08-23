<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    private const USERS = [
        ['name' => 'Budi Hartono', 'email' => 'admin@moneychanger.test', 'password' => 'admin123', 'role' => 'admin'],
        ['name' => 'Siti Rahayu', 'email' => 'owner@moneychanger.test', 'password' => 'owner123', 'role' => 'owner'],
    ];

    public function run(): void
    {
        foreach (self::USERS as $user) {
            User::firstOrCreate(['email' => $user['email']], $user);
        }
    }
}
