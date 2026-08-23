<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    private const BRANCHES = [
        ['name' => 'Cabang Pusat - Jakarta', 'address' => 'Jl. Sudirman No. 1, Jakarta Pusat', 'is_active' => true],
        ['name' => 'Cabang Surabaya', 'address' => 'Jl. Tunjungan No. 25, Surabaya', 'is_active' => true],
        ['name' => 'Cabang Bandung', 'address' => 'Jl. Asia Afrika No. 10, Bandung', 'is_active' => false],
    ];

    public function run(): void
    {
        foreach (self::BRANCHES as $branch) {
            Branch::firstOrCreate(['name' => $branch['name']], $branch);
        }
    }
}
