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

class ReportApiTest extends TestCase
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

    public function test_profit_loss_computes_margin_for_buy_and_sell(): void
    {
        $r = $this->makeRelations();

        // Buy: paid less than default -> profit.
        Transaction::create([
            'transaction_number' => 'TRX-1',
            'type' => 'buy',
            'branch_id' => $r['branch']->id,
            'currency_id' => $r['currency']->id,
            'amount' => 500,
            'rate_default' => 15800,
            'rate_actual' => 15750,
            'total_amount' => 7875000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ]);

        // Sell: charged less than default -> loss.
        Transaction::create([
            'transaction_number' => 'TRX-2',
            'type' => 'sell',
            'branch_id' => $r['branch']->id,
            'currency_id' => $r['currency']->id,
            'amount' => 300,
            'rate_default' => 15850,
            'rate_actual' => 15830,
            'total_amount' => 4749000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ]);

        $response = $this->getJson("/api/reports/profit-loss?branch_id={$r['branch']->id}");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $rows = collect($response->json('data'));
        // buy: (15800-15750)*500 = 25000
        $this->assertEquals(25000, $rows->firstWhere('transaction_number', 'TRX-1')['margin']);
        // sell: (15830-15850)*300 = -6000
        $this->assertEquals(-6000, $rows->firstWhere('transaction_number', 'TRX-2')['margin']);
        $response->assertJsonPath('total_margin', 19000);
    }

    public function test_profit_loss_filters_by_currency_and_date_range(): void
    {
        $r = $this->makeRelations();
        $sgd = Currency::create(['code' => 'SGD']);

        $inRange = Transaction::create([
            'transaction_number' => 'TRX-1',
            'type' => 'buy',
            'branch_id' => $r['branch']->id,
            'currency_id' => $r['currency']->id,
            'amount' => 500,
            'rate_default' => 15800,
            'rate_actual' => 15750,
            'total_amount' => 7875000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ]);
        $inRange->forceFill(['created_at' => '2026-08-16 10:00:00'])->save();

        Transaction::create([
            'transaction_number' => 'TRX-2',
            'type' => 'buy',
            'branch_id' => $r['branch']->id,
            'currency_id' => $sgd->id,
            'amount' => 500,
            'rate_default' => 11700,
            'rate_actual' => 11650,
            'total_amount' => 5825000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ])->forceFill(['created_at' => '2026-08-16 11:00:00'])->save();

        Transaction::create([
            'transaction_number' => 'TRX-3',
            'type' => 'buy',
            'branch_id' => $r['branch']->id,
            'currency_id' => $r['currency']->id,
            'amount' => 100,
            'rate_default' => 15800,
            'rate_actual' => 15750,
            'total_amount' => 1575000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ])->forceFill(['created_at' => '2026-08-10 09:00:00'])->save();

        $response = $this->getJson(
            "/api/reports/profit-loss?branch_id={$r['branch']->id}&currency_id={$r['currency']->id}"
            .'&date_from=2026-08-16&date_to=2026-08-16'
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $inRange->id);
    }

    public function test_employee_performance_aggregates_per_employee_scoped_to_branch_and_period(): void
    {
        $r = $this->makeRelations();
        $otherBranch = Branch::create(['name' => 'Cabang Surabaya']);
        $otherEmployee = Employee::create(['name' => 'Eko', 'position' => 'Teller', 'branch_id' => $otherBranch->id]);
        $r['employee']->update(['branch_id' => $r['branch']->id]);

        Transaction::create([
            'transaction_number' => 'TRX-1',
            'type' => 'buy',
            'branch_id' => $r['branch']->id,
            'currency_id' => $r['currency']->id,
            'amount' => 500,
            'rate_default' => 15800,
            'rate_actual' => 15750,
            'total_amount' => 7875000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ])->forceFill(['created_at' => '2026-08-16 10:00:00'])->save();

        Transaction::create([
            'transaction_number' => 'TRX-2',
            'type' => 'sell',
            'branch_id' => $r['branch']->id,
            'currency_id' => $r['currency']->id,
            'amount' => 300,
            'rate_default' => 15850,
            'rate_actual' => 15830,
            'total_amount' => 4749000,
            'customer_id' => $r['customer']->id,
            'employee_id' => $r['employee']->id,
            'user_id' => $r['user']->id,
        ])->forceFill(['created_at' => '2026-08-10 09:00:00'])->save();

        // Belongs to a different branch's employee entirely; must not leak in.
        Transaction::create([
            'transaction_number' => 'TRX-3',
            'type' => 'buy',
            'branch_id' => $otherBranch->id,
            'currency_id' => $r['currency']->id,
            'amount' => 999,
            'rate_default' => 15800,
            'rate_actual' => 15750,
            'total_amount' => 15784200,
            'customer_id' => $r['customer']->id,
            'employee_id' => $otherEmployee->id,
            'user_id' => $r['user']->id,
        ]);

        $response = $this->getJson(
            "/api/reports/employee-performance?branch_id={$r['branch']->id}&date_from=2026-08-16&date_to=2026-08-16"
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.employee_name', 'Dewi');
        $response->assertJsonPath('data.0.transaction_count', 1);
        $response->assertJsonPath('data.0.total_omzet', 7875000);
        $response->assertJsonPath('data.0.total_margin', 25000);
    }
}
