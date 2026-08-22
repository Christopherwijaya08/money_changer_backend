<?php

namespace Tests\Feature;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_branches_sorted_by_name(): void
    {
        Branch::create(['name' => 'Cabang Surabaya']);
        Branch::create(['name' => 'Cabang Jakarta']);

        $response = $this->getJson('/api/branches');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.name', 'Cabang Jakarta');
        $response->assertJsonPath('data.1.name', 'Cabang Surabaya');
    }

    public function test_index_can_filter_to_active_only(): void
    {
        Branch::create(['name' => 'Cabang Jakarta', 'is_active' => true]);
        Branch::create(['name' => 'Cabang Surabaya', 'is_active' => false]);

        $response = $this->getJson('/api/branches?active_only=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Cabang Jakarta');
    }

    public function test_store_creates_branch_defaulting_to_active(): void
    {
        $response = $this->postJson('/api/branches', [
            'name' => 'Cabang Bandung',
            'address' => 'Jl. Asia Afrika No. 1',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Cabang Bandung');
        $response->assertJsonPath('data.is_active', true);
    }

    public function test_store_rejects_missing_name(): void
    {
        $response = $this->postJson('/api/branches', ['address' => 'Jl. Asia Afrika No. 1']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_show_returns_branch_detail(): void
    {
        $branch = Branch::create(['name' => 'Cabang Jakarta']);

        $response = $this->getJson("/api/branches/{$branch->id}");

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Cabang Jakarta');
    }

    public function test_update_changes_branch_fields(): void
    {
        $branch = Branch::create(['name' => 'Cabang Jakarta', 'is_active' => true]);

        $response = $this->putJson("/api/branches/{$branch->id}", [
            'name' => 'Cabang Jakarta Pusat',
            'is_active' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Cabang Jakarta Pusat');
        $response->assertJsonPath('data.is_active', false);
        $this->assertFalse($branch->fresh()->is_active);
    }

    public function test_update_without_is_active_leaves_status_untouched(): void
    {
        $branch = Branch::create(['name' => 'Cabang Jakarta', 'is_active' => true]);

        $response = $this->putJson("/api/branches/{$branch->id}", ['name' => 'Cabang Jakarta Pusat']);

        $response->assertOk();
        $response->assertJsonPath('data.is_active', true);
        $this->assertTrue($branch->fresh()->is_active);
    }

    public function test_destroy_deletes_branch(): void
    {
        $branch = Branch::create(['name' => 'Cabang Jakarta']);

        $response = $this->deleteJson("/api/branches/{$branch->id}");

        $response->assertNoContent();
        $this->assertModelMissing($branch);
    }
}
