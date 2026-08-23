<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_rejects_non_owner(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/audit-logs')->assertForbidden();
    }

    public function test_index_lists_logs_newest_first_for_owner(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'owner']));
        $user = User::factory()->create(['name' => 'Budi Hartono']);

        $older = AuditLog::create(['user_id' => $user->id, 'action' => 'login', 'description' => 'Login berhasil']);
        $older->forceFill(['created_at' => now()->subHour()])->save();
        AuditLog::create(['user_id' => $user->id, 'action' => 'exchange_rate', 'description' => 'USD berubah']);

        $response = $this->getJson('/api/audit-logs');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.action', 'exchange_rate');
        $response->assertJsonPath('data.0.user_name', 'Budi Hartono');
        $response->assertJsonPath('data.1.action', 'login');
    }

    public function test_index_can_filter_by_action(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'owner']));
        $user = User::factory()->create();

        AuditLog::create(['user_id' => $user->id, 'action' => 'login', 'description' => 'Login berhasil']);
        AuditLog::create(['user_id' => $user->id, 'action' => 'transaction_delete', 'description' => 'TRX-1 dihapus']);

        $response = $this->getJson('/api/audit-logs?action=transaction_delete');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.action', 'transaction_delete');
    }
}
