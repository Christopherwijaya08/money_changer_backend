<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashDeposit;
use App\Models\CashReconciliation;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashReconciliationApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeRelations(): array
    {
        return [
            'branch' => Branch::create(['name' => 'Cabang Jakarta']),
            'currency' => Currency::create(['code' => 'USD']),
            'employee' => Employee::create(['name' => 'Dewi', 'position' => 'Teller']),
            'user' => User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret']),
            'customer' => Customer::create(['name' => 'Andi', 'identity_number' => '123', 'phone' => '0812']),
        ];
    }

    public function test_index_computes_opening_cash_in_out_and_system_balance_for_the_date(): void
    {
        $r = $this->makeRelations();

        // Yesterday: a deposit that becomes the opening balance for today.
        // created_at isn't fillable (mass-assignment guard), so it's forced in after create().
        CashDeposit::create([
            'branch_id' => $r['branch']->id,
            'currency_id' => $r['currency']->id,
            'amount' => 10000,
            'created_by' => $r['user']->id,
        ])->forceFill(['created_at' => '2026-08-15 09:00:00'])->save();

        // Today: a buy (cash in) and a sell (cash out).
        Transaction::create([
            'transaction_number' => 'TRX-1',
            'type' => 'buy',
            'branch_id' => $r['branch']->id,
            'currency_id' => $r['currency']->id,
            'amount' => 5000,
            'rate_default' => 15750,
            'rate_actual' => 15750,
            'total_amount' => 78750000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ])->forceFill(['created_at' => '2026-08-16 10:00:00'])->save();

        Transaction::create([
            'transaction_number' => 'TRX-2',
            'type' => 'sell',
            'branch_id' => $r['branch']->id,
            'currency_id' => $r['currency']->id,
            'amount' => 2000,
            'rate_default' => 15850,
            'rate_actual' => 15850,
            'total_amount' => 31700000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ])->forceFill(['created_at' => '2026-08-16 11:00:00'])->save();

        $response = $this->getJson("/api/cash-reconciliations?branch_id={$r['branch']->id}&date=2026-08-16");

        $response->assertOk();
        $usdEntry = collect($response->json('data'))->firstWhere('currency_code', 'USD');
        $this->assertEquals(10000, $usdEntry['opening_balance']);
        $this->assertEquals(5000, $usdEntry['cash_in']);
        $this->assertEquals(2000, $usdEntry['cash_out']);
        $this->assertEquals(13000, $usdEntry['system_balance']);
        $this->assertNull($usdEntry['physical_balance']);
    }

    public function test_store_saves_physical_count_and_computes_difference(): void
    {
        $r = $this->makeRelations();

        CashDeposit::create([
            'branch_id' => $r['branch']->id,
            'currency_id' => $r['currency']->id,
            'amount' => 10000,
            'created_by' => $r['user']->id,
        ])->forceFill(['created_at' => '2026-08-16 09:00:00'])->save();

        $response = $this->postJson('/api/cash-reconciliations', [
            'branch_id' => $r['branch']->id,
            'currency_id' => $r['currency']->id,
            'date' => '2026-08-16',
            'physical_balance' => 9950,
            'user_id' => $r['user']->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.system_balance', '10000.00');
        $response->assertJsonPath('data.physical_balance', '9950.00');
        $response->assertJsonPath('data.difference', '-50.00');

        $this->assertDatabaseCount('cash_reconciliations', 1);
    }

    public function test_store_upserts_existing_reconciliation_for_same_branch_currency_date(): void
    {
        $r = $this->makeRelations();

        $this->postJson('/api/cash-reconciliations', [
            'branch_id' => $r['branch']->id,
            'currency_id' => $r['currency']->id,
            'date' => '2026-08-16',
            'physical_balance' => 100,
            'user_id' => $r['user']->id,
        ]);

        $response = $this->postJson('/api/cash-reconciliations', [
            'branch_id' => $r['branch']->id,
            'currency_id' => $r['currency']->id,
            'date' => '2026-08-16',
            'physical_balance' => 250,
            'user_id' => $r['user']->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('cash_reconciliations', 1);
        $this->assertEquals(1, CashReconciliation::count());
        $response->assertJsonPath('data.physical_balance', '250.00');
    }

    public function test_store_rejects_missing_physical_balance(): void
    {
        $r = $this->makeRelations();

        $response = $this->postJson('/api/cash-reconciliations', [
            'currency_id' => $r['currency']->id,
            'date' => '2026-08-16',
            'user_id' => $r['user']->id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['physical_balance']);
    }
}
