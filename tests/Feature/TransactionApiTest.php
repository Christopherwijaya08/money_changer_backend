<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

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

    public function test_index_filters_by_requires_review(): void
    {
        $r = $this->makeRelations();

        $needsReview = Transaction::create([
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
            'requires_review' => true,
        ]);

        Transaction::create([
            'transaction_number' => 'TRX-20260816-002',
            'type' => 'sell',
            'currency_id' => $r['currency']->id,
            'amount' => 200,
            'rate_default' => 15750,
            'rate_actual' => 15780,
            'total_amount' => 3156000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
            'requires_review' => false,
        ]);

        $response = $this->getJson('/api/transactions?requires_review=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $needsReview->id);
    }

    public function test_index_filters_by_branch(): void
    {
        $r = $this->makeRelations();
        $jakarta = Branch::create(['name' => 'Cabang Jakarta']);
        $surabaya = Branch::create(['name' => 'Cabang Surabaya']);

        $jakartaTransaction = Transaction::create([
            'transaction_number' => 'TRX-20260816-001',
            'type' => 'buy',
            'branch_id' => $jakarta->id,
            'currency_id' => $r['currency']->id,
            'amount' => 500,
            'rate_default' => 15750,
            'rate_actual' => 15780,
            'total_amount' => 7890000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ]);

        Transaction::create([
            'transaction_number' => 'TRX-20260816-002',
            'type' => 'sell',
            'branch_id' => $surabaya->id,
            'currency_id' => $r['currency']->id,
            'amount' => 2000,
            'rate_default' => 11600,
            'rate_actual' => 11730,
            'total_amount' => 23460000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ]);

        $response = $this->getJson('/api/transactions?branch_id='.$jakarta->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $jakartaTransaction->id);
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

    public function test_update_changes_amount_and_rate_and_logs_the_diff(): void
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

        $response = $this->putJson("/api/transactions/{$transaction->id}", [
            'type' => 'buy',
            'currency_id' => $r['currency']->id,
            'amount' => 500,
            'rate_default' => 15750,
            'rate_actual' => 15800,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.rate_actual', '15800.00');
        $this->assertEquals(7900000, $transaction->fresh()->total_amount);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction_edit',
            'description' => 'TRX-20260816-001: kurs aktual 15.780 → 15.800',
        ]);
    }

    public function test_destroy_deletes_transaction_and_logs_it(): void
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

        $response = $this->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertNoContent();
        $this->assertModelMissing($transaction);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction_delete',
            'description' => 'TRX-20260816-001 dihapus',
        ]);
    }
}
