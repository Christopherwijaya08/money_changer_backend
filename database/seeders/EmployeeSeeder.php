<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    private const EMPLOYEES = [
        ['name' => 'Dewi Anggraini', 'position' => 'Teller', 'is_active' => true],
        ['name' => 'Eko Prasetyo', 'position' => 'Teller', 'is_active' => true],
        ['name' => 'Fajar Nugroho', 'position' => 'Supervisor', 'is_active' => true],
        ['name' => 'Guntur Saputra', 'position' => 'Teller', 'is_active' => false],
    ];

    public function run(): void
    {
        foreach (self::EMPLOYEES as $employee) {
            Employee::firstOrCreate(['name' => $employee['name']], $employee);
        }
    }
}
