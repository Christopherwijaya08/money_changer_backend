<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExchangeRateApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $actingUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingUser = User::factory()->create();
        Sanctum::actingAs($this->actingUser);
    }

    public function test_index_returns_each_currency_with_its_latest_rate(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret']);
        $usd = Currency::create(['code' => 'USD']);
        $sgd = Currency::create(['code' => 'SGD']);

        ExchangeRate::create([
            'currency_id' => $usd->id,
            'rate_buy' => 15700,
            'rate_sell' => 15800,
            'effective_date' => now()->subDay()->toDateString(),
            'created_by' => $user->id,
        ]);
        $latestUsd = ExchangeRate::create([
            'currency_id' => $usd->id,
            'rate_buy' => 15750,
            'rate_sell' => 15850,
            'effective_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
        ExchangeRate::create([
            'currency_id' => $sgd->id,
            'rate_buy' => 11600,
            'rate_sell' => 11750,
            'effective_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $response = $this->getJson('/api/exchange-rates');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        $usdEntry = collect($response->json('data'))->firstWhere('currency_code', 'USD');
        $this->assertEquals('15750.00', $usdEntry['rate_buy']);
        $this->assertEquals('15850.00', $usdEntry['rate_sell']);
        $this->assertEquals('Admin', $usdEntry['updated_by']);

        $sgdEntry = collect($response->json('data'))->firstWhere('currency_code', 'SGD');
        $this->assertEquals('11600.00', $sgdEntry['rate_buy']);
    }

    public function test_index_handles_currency_without_any_rate_yet(): void
    {
        Currency::create(['code' => 'EUR']);

        $response = $this->getJson('/api/exchange-rates');

        $response->assertOk();
        $response->assertJsonPath('data.0.currency_code', 'EUR');
        $response->assertJsonPath('data.0.rate_buy', null);
    }

    public function test_update_creates_first_rate_without_history(): void
    {
        $usd = Currency::create(['code' => 'USD']);

        $response = $this->putJson("/api/exchange-rates/{$usd->id}", [
            'rate_buy' => 15750,
            'rate_sell' => 15850,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.rate_buy', '15750.00');
        $this->assertDatabaseCount('exchange_rate_history', 0);
    }

    public function test_update_overwrites_todays_rate_and_logs_history(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret']);
        $usd = Currency::create(['code' => 'USD']);
        $rate = ExchangeRate::create([
            'currency_id' => $usd->id,
            'rate_buy' => 15700,
            'rate_sell' => 15800,
            'effective_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
        $rate->forceFill(['created_at' => now()->subDay()])->save();

        $response = $this->putJson("/api/exchange-rates/{$usd->id}", [
            'rate_buy' => 15750,
            'rate_sell' => 15850,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.rate_buy', '15750.00');
        // "updated_at" must reflect the edit, not the original seed/creation time.
        $this->assertNotEquals($rate->created_at->toJSON(), $response->json('data.updated_at'));
        $this->assertDatabaseCount('exchange_rates', 1);
        $this->assertDatabaseHas('exchange_rate_history', [
            'old_buy' => '15700.00',
            'old_sell' => '15800.00',
            'new_buy' => '15750.00',
            'new_sell' => '15850.00',
            'changed_by' => $this->actingUser->id,
        ]);
    }

    public function test_update_rejects_invalid_payload(): void
    {
        $usd = Currency::create(['code' => 'USD']);

        $response = $this->putJson("/api/exchange-rates/{$usd->id}", [
            'rate_buy' => -1,
            'rate_sell' => 15850,
        ]);

        $response->assertStatus(422);
    }

    public function test_history_lists_changes_for_that_currency_only_newest_first(): void
    {
        $usd = Currency::create(['code' => 'USD']);
        $sgd = Currency::create(['code' => 'SGD']);

        $this->putJson("/api/exchange-rates/{$usd->id}", ['rate_buy' => 15700, 'rate_sell' => 15800]);
        $this->putJson("/api/exchange-rates/{$usd->id}", ['rate_buy' => 15750, 'rate_sell' => 15850]);
        $this->putJson("/api/exchange-rates/{$sgd->id}", ['rate_buy' => 11600, 'rate_sell' => 11750]);
        $this->putJson("/api/exchange-rates/{$sgd->id}", ['rate_buy' => 11650, 'rate_sell' => 11800]);

        $response = $this->getJson("/api/exchange-rates/{$usd->id}/history");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.old_buy', '15700.00');
        $response->assertJsonPath('data.0.new_buy', '15750.00');
        $response->assertJsonPath('data.0.changed_by', $this->actingUser->name);
    }
}
