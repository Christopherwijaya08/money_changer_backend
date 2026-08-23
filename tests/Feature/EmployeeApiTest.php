<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_index_returns_all_employees_sorted_by_name(): void
    {
        Employee::create(['name' => 'Fajar Nugroho', 'position' => 'Supervisor', 'is_active' => true]);
        Employee::create(['name' => 'Dewi Anggraini', 'position' => 'Teller', 'is_active' => true]);
        Employee::create(['name' => 'Guntur Saputra', 'position' => 'Teller', 'is_active' => false]);

        $response = $this->getJson('/api/employees');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.name', 'Dewi Anggraini');
        $response->assertJsonPath('data.1.name', 'Fajar Nugroho');
        $response->assertJsonPath('data.2.name', 'Guntur Saputra');
    }

    public function test_index_can_filter_to_active_only(): void
    {
        Employee::create(['name' => 'Dewi Anggraini', 'is_active' => true]);
        Employee::create(['name' => 'Guntur Saputra', 'is_active' => false]);

        $response = $this->getJson('/api/employees?active_only=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Dewi Anggraini');
    }

    public function test_index_filters_by_branch(): void
    {
        $jakarta = Branch::create(['name' => 'Cabang Jakarta']);
        $surabaya = Branch::create(['name' => 'Cabang Surabaya']);
        Employee::create(['name' => 'Dewi Anggraini', 'is_active' => true, 'branch_id' => $jakarta->id]);
        Employee::create(['name' => 'Eko Prasetyo', 'is_active' => true, 'branch_id' => $surabaya->id]);

        $response = $this->getJson('/api/employees?branch_id='.$jakarta->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Dewi Anggraini');
    }

    public function test_store_creates_employee(): void
    {
        $branch = Branch::create(['name' => 'Cabang Pusat - Jakarta']);

        $response = $this->postJson('/api/employees', [
            'name' => 'Hendra Wijaya',
            'position' => 'Teller',
            'branch_id' => $branch->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Hendra Wijaya');
        $response->assertJsonPath('data.branch_name', 'Cabang Pusat - Jakarta');
        $response->assertJsonPath('data.is_active', true);
    }

    public function test_store_rejects_missing_name(): void
    {
        $response = $this->postJson('/api/employees', ['position' => 'Teller']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_update_changes_employee_fields(): void
    {
        $employee = Employee::create(['name' => 'Dewi Anggraini', 'position' => 'Teller', 'is_active' => true]);

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'name' => 'Dewi Anggraini',
            'position' => 'Supervisor',
            'is_active' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.position', 'Supervisor');
        $response->assertJsonPath('data.is_active', false);
        $this->assertFalse($employee->fresh()->is_active);
    }

    public function test_update_without_is_active_leaves_status_untouched(): void
    {
        $employee = Employee::create(['name' => 'Dewi Anggraini', 'position' => 'Teller', 'is_active' => true]);

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'name' => 'Dewi Anggraini',
            'position' => 'Supervisor',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.is_active', true);
        $this->assertTrue($employee->fresh()->is_active);
    }
}
