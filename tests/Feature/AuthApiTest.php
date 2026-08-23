<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_endpoint_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/currencies')->assertUnauthorized();
    }

    public function test_login_issues_a_token_for_correct_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@moneychanger.test',
            'password' => 'admin123',
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@moneychanger.test',
            'password' => 'admin123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.role', 'admin');
        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'login']);
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create(['email' => 'admin@moneychanger.test', 'password' => 'admin123']);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@moneychanger.test',
            'password' => 'salah',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_login_rejects_inactive_account(): void
    {
        User::factory()->create([
            'email' => 'admin@moneychanger.test',
            'password' => 'admin123',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@moneychanger.test',
            'password' => 'admin123',
        ]);

        $response->assertUnprocessable();
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/logout')->assertNoContent();
    }

    public function test_change_password_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpass1']);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/change-password', [
            'current_password' => 'wrongpass',
            'new_password' => 'newpass1',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['current_password']);
    }

    public function test_change_password_updates_the_hash(): void
    {
        $user = User::factory()->create(['password' => 'oldpass1']);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/change-password', [
            'current_password' => 'oldpass1',
            'new_password' => 'newpass1',
        ]);

        $response->assertNoContent();
        $this->assertTrue(Hash::check('newpass1', $user->fresh()->password));
    }
}
