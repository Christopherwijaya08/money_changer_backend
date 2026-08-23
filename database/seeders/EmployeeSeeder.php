<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    // branch keyed by name so seeding order doesn't have to match BranchSeeder's.
    private const EMPLOYEES = [
        ['name' => 'Dewi Anggraini', 'position' => 'Teller', 'is_active' => true, 'branch' => 'Cabang Pusat - Jakarta'],
        ['name' => 'Eko Prasetyo', 'position' => 'Teller', 'is_active' => true, 'branch' => 'Cabang Surabaya'],
        ['name' => 'Fajar Nugroho', 'position' => 'Supervisor', 'is_active' => true, 'branch' => 'Cabang Pusat - Jakarta'],
        ['name' => 'Guntur Saputra', 'position' => 'Teller', 'is_active' => false, 'branch' => 'Cabang Surabaya'],
    ];

    public function run(): void
    {
        foreach (self::EMPLOYEES as $employee) {
            $branchId = Branch::where('name', $employee['branch'])->value('id');
            unset($employee['branch']);
            Employee::firstOrCreate(['name' => $employee['name']], [...$employee, 'branch_id' => $branchId]);
        }
    }
}
