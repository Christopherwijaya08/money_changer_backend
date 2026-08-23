<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

// ponytail: mirrors the hardcoded mock accounts in frontend LoginPage.jsx so their
// user_id is a real row until Sanctum login (Fase 5) issues real sessions.
class UserSeeder extends Seeder
{
    private const USERS = [
        ['name' => 'Budi Hartono', 'email' => 'admin@moneychanger.test', 'password' => 'admin123'],
        ['name' => 'Siti Rahayu', 'email' => 'owner@moneychanger.test', 'password' => 'owner123'],
    ];

    public function run(): void
    {
        foreach (self::USERS as $user) {
            User::firstOrCreate(['email' => $user['email']], $user);
        }
    }
}
