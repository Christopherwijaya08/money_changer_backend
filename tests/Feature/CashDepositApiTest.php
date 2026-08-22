<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashDeposit;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashDepositApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_cash_deposit(): void
    {
        $branch = Branch::create(['name' => 'Cabang Jakarta']);
        $currency = Currency::create(['code' => 'USD']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret']);

        $response = $this->postJson('/api/cash-deposits', [
            'branch_id' => $branch->id,
            'currency_id' => $currency->id,
            'amount' => 20000,
            'rate' => 15750,
            'note' => 'Modal awal dari Owner',
            'user_id' => $user->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.currency_code', 'USD');
        $response->assertJsonPath('data.branch_name', 'Cabang Jakarta');
        $response->assertJsonPath('data.amount', '20000.00');
        $response->assertJsonPath('data.created_by', 'Admin');
    }

    public function test_store_rejects_invalid_payload(): void
    {
        $response = $this->postJson('/api/cash-deposits', ['amount' => -5]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['currency_id', 'amount', 'rate', 'user_id']);
    }

    public function test_index_lists_and_filters_by_branch_and_currency(): void
    {
        $jakarta = Branch::create(['name' => 'Cabang Jakarta']);
        $surabaya = Branch::create(['name' => 'Cabang Surabaya']);
        $usd = Currency::create(['code' => 'USD']);
        $sgd = Currency::create(['code' => 'SGD']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret']);

        $matching = CashDeposit::create([
            'branch_id' => $jakarta->id,
            'currency_id' => $usd->id,
            'amount' => 20000,
            'rate' => 15750,
            'created_by' => $user->id,
        ]);

        CashDeposit::create([
            'branch_id' => $jakarta->id,
            'currency_id' => $sgd->id,
            'amount' => 15000,
            'rate' => 11600,
            'created_by' => $user->id,
        ]);

        CashDeposit::create([
            'branch_id' => $surabaya->id,
            'currency_id' => $usd->id,
            'amount' => 10000,
            'rate' => 15700,
            'created_by' => $user->id,
        ]);

        $response = $this->getJson("/api/cash-deposits?branch_id={$jakarta->id}&currency_id={$usd->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $matching->id);
    }
}
