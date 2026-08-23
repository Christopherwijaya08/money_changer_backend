<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashDeposit;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashBalanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_computes_balance_from_deposits_and_transactions_scoped_to_branch(): void
    {
        $jakarta = Branch::create(['name' => 'Cabang Jakarta']);
        $surabaya = Branch::create(['name' => 'Cabang Surabaya']);
        $usd = Currency::create(['code' => 'USD', 'name' => 'Dolar Amerika Serikat']);
        $employee = Employee::create(['name' => 'Dewi', 'position' => 'Teller']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret']);
        $customer = Customer::create(['name' => 'Andi', 'identity_number' => '123', 'phone' => '0812']);

        CashDeposit::create([
            'branch_id' => $jakarta->id,
            'currency_id' => $usd->id,
            'amount' => 20000,
            'created_by' => $user->id,
        ]);

        Transaction::create([
            'transaction_number' => 'TRX-1',
            'type' => 'buy',
            'branch_id' => $jakarta->id,
            'currency_id' => $usd->id,
            'amount' => 5000,
            'rate_default' => 15750,
            'rate_actual' => 15750,
            'total_amount' => 78750000,
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ]);

        Transaction::create([
            'transaction_number' => 'TRX-2',
            'type' => 'sell',
            'branch_id' => $jakarta->id,
            'currency_id' => $usd->id,
            'amount' => 3000,
            'rate_default' => 15850,
            'rate_actual' => 15850,
            'total_amount' => 47550000,
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ]);

        // A Surabaya-only transaction that must not leak into Jakarta's balance.
        Transaction::create([
            'transaction_number' => 'TRX-3',
            'type' => 'buy',
            'branch_id' => $surabaya->id,
            'currency_id' => $usd->id,
            'amount' => 9999,
            'rate_default' => 15750,
            'rate_actual' => 15750,
            'total_amount' => 157484250,
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ]);

        $response = $this->getJson("/api/cash-balances?branch_id={$jakarta->id}");

        $response->assertOk();
        $usdEntry = collect($response->json('data'))->firstWhere('currency_code', 'USD');
        // 20000 (setor) + 5000 (beli) - 3000 (jual) = 22000
        $this->assertEquals(22000, $usdEntry['balance']);
    }

    public function test_index_returns_zero_balance_for_currency_without_any_movement(): void
    {
        Currency::create(['code' => 'EUR']);

        $response = $this->getJson('/api/cash-balances');

        $response->assertOk();
        $eurEntry = collect($response->json('data'))->firstWhere('currency_code', 'EUR');
        $this->assertEquals(0, $eurEntry['balance']);
    }
}
