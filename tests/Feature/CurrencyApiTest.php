<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurrencyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_index_lists_currencies_sorted_by_code(): void
    {
        Currency::create(['code' => 'USD', 'name' => 'Dolar Amerika Serikat']);
        Currency::create(['code' => 'AUD', 'name' => 'Dolar Australia']);

        $response = $this->getJson('/api/currencies');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.code', 'AUD');
        $response->assertJsonPath('data.1.code', 'USD');
    }

    public function test_index_can_filter_to_active_only(): void
    {
        Currency::create(['code' => 'USD', 'is_active' => true]);
        Currency::create(['code' => 'AUD', 'is_active' => false]);

        $response = $this->getJson('/api/currencies?active_only=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.code', 'USD');
    }

    public function test_store_creates_currency_defaulting_to_active(): void
    {
        $response = $this->postJson('/api/currencies', [
            'code' => 'gbp',
            'name' => 'Poundsterling Inggris',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.code', 'GBP');
        $response->assertJsonPath('data.is_active', true);
    }

    public function test_store_rejects_invalid_or_duplicate_code(): void
    {
        Currency::create(['code' => 'USD']);

        $this->postJson('/api/currencies', ['code' => 'US'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);

        $this->postJson('/api/currencies', ['code' => 'USD'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_show_returns_currency_detail(): void
    {
        $currency = Currency::create(['code' => 'USD', 'name' => 'Dolar Amerika Serikat']);

        $response = $this->getJson("/api/currencies/{$currency->id}");

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Dolar Amerika Serikat');
    }

    public function test_update_changes_name_and_keeps_own_code_valid(): void
    {
        $currency = Currency::create(['code' => 'USD', 'name' => 'Dolar AS', 'is_active' => true]);

        $response = $this->putJson("/api/currencies/{$currency->id}", [
            'code' => 'USD',
            'name' => 'Dolar Amerika Serikat',
            'is_active' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Dolar Amerika Serikat');
        $response->assertJsonPath('data.is_active', false);
    }

    public function test_update_without_is_active_leaves_status_untouched(): void
    {
        $currency = Currency::create(['code' => 'USD', 'is_active' => true]);

        $response = $this->putJson("/api/currencies/{$currency->id}", ['code' => 'USD', 'name' => 'Dolar AS']);

        $response->assertOk();
        $response->assertJsonPath('data.is_active', true);
        $this->assertTrue($currency->fresh()->is_active);
    }

    public function test_destroy_deletes_currency_without_history(): void
    {
        $currency = Currency::create(['code' => 'USD']);

        $response = $this->deleteJson("/api/currencies/{$currency->id}");

        $response->assertNoContent();
        $this->assertModelMissing($currency);
    }

    public function test_destroy_rejects_currency_with_transaction_history(): void
    {
        $currency = Currency::create(['code' => 'USD']);
        $employee = Employee::create(['name' => 'Dewi', 'position' => 'Teller']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret']);
        $customer = Customer::create(['name' => 'Andi', 'identity_number' => '123', 'phone' => '0812']);
        Transaction::create([
            'transaction_number' => 'TRX-20260816-001',
            'type' => 'buy',
            'currency_id' => $currency->id,
            'amount' => 500,
            'rate_default' => 15750,
            'rate_actual' => 15780,
            'total_amount' => 7890000,
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJson("/api/currencies/{$currency->id}");

        $response->assertStatus(409);
        $this->assertModelExists($currency);
    }
}
