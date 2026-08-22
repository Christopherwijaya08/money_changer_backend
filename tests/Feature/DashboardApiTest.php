<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_computes_omzet_and_transaction_count_for_the_date_and_branch(): void
    {
        $jakarta = Branch::create(['name' => 'Cabang Jakarta']);
        $surabaya = Branch::create(['name' => 'Cabang Surabaya']);
        $currency = Currency::create(['code' => 'USD']);
        $employee = Employee::create(['name' => 'Dewi', 'position' => 'Teller']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret']);
        $customer = Customer::create(['name' => 'Andi', 'identity_number' => '123', 'phone' => '0812']);

        Transaction::create([
            'transaction_number' => 'TRX-1',
            'type' => 'buy',
            'branch_id' => $jakarta->id,
            'currency_id' => $currency->id,
            'amount' => 500,
            'rate_default' => 15750,
            'rate_actual' => 15750,
            'total_amount' => 7875000,
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ])->forceFill(['created_at' => '2026-08-16 09:00:00'])->save();

        Transaction::create([
            'transaction_number' => 'TRX-2',
            'type' => 'sell',
            'branch_id' => $jakarta->id,
            'currency_id' => $currency->id,
            'amount' => 300,
            'rate_default' => 15850,
            'rate_actual' => 15850,
            'total_amount' => 4755000,
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ])->forceFill(['created_at' => '2026-08-16 14:00:00'])->save();

        // Different branch, same date -> must not leak in.
        Transaction::create([
            'transaction_number' => 'TRX-3',
            'type' => 'buy',
            'branch_id' => $surabaya->id,
            'currency_id' => $currency->id,
            'amount' => 999,
            'rate_default' => 15750,
            'rate_actual' => 15750,
            'total_amount' => 15734250,
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ])->forceFill(['created_at' => '2026-08-16 10:00:00'])->save();

        // Same branch, different date -> must not leak in.
        Transaction::create([
            'transaction_number' => 'TRX-4',
            'type' => 'buy',
            'branch_id' => $jakarta->id,
            'currency_id' => $currency->id,
            'amount' => 100,
            'rate_default' => 15750,
            'rate_actual' => 15750,
            'total_amount' => 1575000,
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ])->forceFill(['created_at' => '2026-08-15 09:00:00'])->save();

        $response = $this->getJson("/api/dashboard/summary?branch_id={$jakarta->id}&date=2026-08-16");

        $response->assertOk();
        $response->assertJsonPath('date', '2026-08-16');
        $response->assertJsonPath('omzet', 12630000);
        $response->assertJsonPath('transaction_count', 2);
    }

    public function test_summary_defaults_to_today_and_returns_zero_when_no_transactions(): void
    {
        $response = $this->getJson('/api/dashboard/summary');

        $response->assertOk();
        $response->assertJsonPath('date', now()->toDateString());
        $response->assertJsonPath('omzet', 0);
        $response->assertJsonPath('transaction_count', 0);
    }

    public function test_trend_returns_one_row_per_day_in_the_period(): void
    {
        $jakarta = Branch::create(['name' => 'Cabang Jakarta']);
        $currency = Currency::create(['code' => 'USD']);
        $employee = Employee::create(['name' => 'Dewi', 'position' => 'Teller']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret']);
        $customer = Customer::create(['name' => 'Andi', 'identity_number' => '123', 'phone' => '0812']);

        Transaction::create([
            'transaction_number' => 'TRX-1',
            'type' => 'buy',
            'branch_id' => $jakarta->id,
            'currency_id' => $currency->id,
            'amount' => 500,
            'rate_default' => 15750,
            'rate_actual' => 15750,
            'total_amount' => 7875000,
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ])->forceFill(['created_at' => '2026-08-16 09:00:00'])->save();

        // Outside the 3-day window ending 2026-08-16 (i.e. before 2026-08-14) -> excluded.
        Transaction::create([
            'transaction_number' => 'TRX-2',
            'type' => 'buy',
            'branch_id' => $jakarta->id,
            'currency_id' => $currency->id,
            'amount' => 200,
            'rate_default' => 15750,
            'rate_actual' => 15750,
            'total_amount' => 3150000,
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ])->forceFill(['created_at' => '2026-08-10 09:00:00'])->save();

        $response = $this->getJson(
            "/api/dashboard/trend?branch_id={$jakarta->id}&period=3&end_date=2026-08-16"
        );

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.date', '2026-08-14');
        $response->assertJsonPath('data.0.omzet', 0);
        $response->assertJsonPath('data.1.date', '2026-08-15');
        $response->assertJsonPath('data.1.omzet', 0);
        $response->assertJsonPath('data.2.date', '2026-08-16');
        $response->assertJsonPath('data.2.omzet', 7875000);
    }
}
