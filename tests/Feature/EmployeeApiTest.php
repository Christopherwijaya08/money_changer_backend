<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_returns_active_employees_sorted_by_name(): void
    {
        Employee::create(['name' => 'Fajar Nugroho', 'position' => 'Supervisor', 'is_active' => true]);
        Employee::create(['name' => 'Dewi Anggraini', 'position' => 'Teller', 'is_active' => true]);
        Employee::create(['name' => 'Guntur Saputra', 'position' => 'Teller', 'is_active' => false]);

        $response = $this->getJson('/api/employees');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.name', 'Dewi Anggraini');
        $response->assertJsonPath('data.1.name', 'Fajar Nugroho');
    }
}
