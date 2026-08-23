<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $actingUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingUser = User::factory()->create();
        Sanctum::actingAs($this->actingUser);
    }

    public function test_show_returns_default_threshold_when_unset(): void
    {
        $response = $this->getJson('/api/settings/threshold');

        $response->assertOk();
        $response->assertJsonPath('data.review_threshold', '50000000.00');
    }

    public function test_update_changes_threshold(): void
    {
        $response = $this->putJson('/api/settings/threshold', [
            'review_threshold' => 75000000,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.review_threshold', '75000000.00');
        $response->assertJsonPath('data.updated_by', $this->actingUser->name);

        $again = $this->getJson('/api/settings/threshold');
        $again->assertJsonPath('data.review_threshold', '75000000.00');
    }

    public function test_update_rejects_invalid_payload(): void
    {
        $response = $this->putJson('/api/settings/threshold', ['review_threshold' => -5]);

        $response->assertStatus(422);
    }
}
