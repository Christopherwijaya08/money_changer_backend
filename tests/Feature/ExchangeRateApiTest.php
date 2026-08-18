<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateApiTest extends TestCase
{
    use RefreshDatabase;

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
}
