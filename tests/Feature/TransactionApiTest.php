<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeRelations(): array
    {
        return [
            'currency' => Currency::create(['code' => 'USD']),
            'employee' => Employee::create(['name' => 'Dewi', 'position' => 'Teller']),
            'user' => User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret']),
            'customer' => Customer::create([
                'name' => 'Andi Wijaya',
                'identity_number' => '3171234567890001',
                'phone' => '081234567890',
            ]),
        ];
    }

    public function test_store_creates_transaction_with_computed_total_and_review_flag(): void
    {
        $r = $this->makeRelations();

        $response = $this->postJson('/api/transactions', [
            'type' => 'buy',
            'currency_id' => $r['currency']->id,
            'amount' => 4000,
            'rate_default' => 15750,
            'rate_actual' => 15840,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.currency_code', 'USD');
        $response->assertJsonPath('data.customer_name', 'Andi Wijaya');
        $response->assertJsonPath('data.requires_review', true);
        $this->assertEquals(63360000, $response->json('data.total_amount'));
        $this->assertStringStartsWith('TRX-', $response->json('data.transaction_number'));
    }

    public function test_store_flags_review_using_the_configured_threshold(): void
    {
        $r = $this->makeRelations();
        Setting::current()->update(['review_threshold' => 5000000]);

        $response = $this->postJson('/api/transactions', [
            'type' => 'buy',
            'currency_id' => $r['currency']->id,
            'amount' => 400,
            'rate_default' => 15750,
            'rate_actual' => 15840,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ]);

        $response->assertCreated();
        $this->assertEquals(6336000, $response->json('data.total_amount'));
        $response->assertJsonPath('data.requires_review', true);
    }

    public function test_store_rejects_invalid_payload(): void
    {
        $response = $this->postJson('/api/transactions', ['type' => 'buy']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['currency_id', 'amount', 'customer_id', 'employee_id']);
    }

    public function test_index_lists_and_filters_transactions(): void
    {
        $r = $this->makeRelations();

        $matching = Transaction::create([
            'transaction_number' => 'TRX-20260816-001',
            'type' => 'buy',
            'currency_id' => $r['currency']->id,
            'amount' => 500,
            'rate_default' => 15750,
            'rate_actual' => 15780,
            'total_amount' => 7890000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ]);

        $otherCurrency = Currency::create(['code' => 'SGD']);
        Transaction::create([
            'transaction_number' => 'TRX-20260816-002',
            'type' => 'sell',
            'currency_id' => $otherCurrency->id,
            'amount' => 2000,
            'rate_default' => 11600,
            'rate_actual' => 11730,
            'total_amount' => 23460000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ]);

        $response = $this->getJson('/api/transactions?currency_id='.$r['currency']->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_show_returns_transaction_detail_for_nota(): void
    {
        $r = $this->makeRelations();

        $transaction = Transaction::create([
            'transaction_number' => 'TRX-20260816-001',
            'type' => 'buy',
            'currency_id' => $r['currency']->id,
            'amount' => 500,
            'rate_default' => 15750,
            'rate_actual' => 15780,
            'total_amount' => 7890000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ]);

        $response = $this->getJson("/api/transactions/{$transaction->id}");

        $response->assertOk();
        $response->assertJsonPath('data.transaction_number', 'TRX-20260816-001');
        $response->assertJsonPath('data.currency_code', 'USD');
        $response->assertJsonPath('data.customer_name', 'Andi Wijaya');
        $response->assertJsonPath('data.employee_name', 'Dewi');
        $response->assertJsonPath('data.total_amount', '7890000.00');
    }

    public function test_show_returns_404_for_unknown_transaction(): void
    {
        $response = $this->getJson('/api/transactions/999999');

        $response->assertNotFound();
    }
}
