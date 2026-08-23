<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_rejects_non_owner(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_index_lists_accounts_for_owner(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'owner']));
        User::factory()->create(['name' => 'Budi Hartono', 'role' => 'admin']);

        $response = $this->getJson('/api/admin/users');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_store_creates_account_with_hashed_password(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'owner']));

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Hendra Wijaya',
            'email' => 'hendra@moneychanger.test',
            'role' => 'admin',
            'password' => 'secret123',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Hendra Wijaya');
        $response->assertJsonPath('data.role', 'admin');
        $response->assertJsonPath('data.is_active', true);

        $created = User::where('email', 'hendra@moneychanger.test')->firstOrFail();
        $this->assertTrue(Hash::check('secret123', $created->password));
    }

    public function test_store_rejects_non_owner(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Hendra Wijaya',
            'email' => 'hendra@moneychanger.test',
            'role' => 'admin',
            'password' => 'secret123',
        ]);

        $response->assertForbidden();
    }

    public function test_update_changes_role_and_deactivates_without_touching_password(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'owner']));
        $account = User::factory()->create(['role' => 'admin']);
        $originalHash = $account->password;

        $response = $this->putJson("/api/admin/users/{$account->id}", [
            'name' => $account->name,
            'email' => $account->email,
            'role' => 'owner',
            'is_active' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.role', 'owner');
        $response->assertJsonPath('data.is_active', false);
        $this->assertEquals($originalHash, $account->fresh()->password);
    }

    public function test_update_changes_password_when_provided(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'owner']));
        $account = User::factory()->create(['role' => 'admin']);

        $this->putJson("/api/admin/users/{$account->id}", [
            'name' => $account->name,
            'email' => $account->email,
            'role' => 'admin',
            'password' => 'newpass123',
        ]);

        $this->assertTrue(Hash::check('newpass123', $account->fresh()->password));
    }
}
