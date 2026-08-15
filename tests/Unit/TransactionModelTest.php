<?php

namespace Tests\Unit;

use App\Http\Resources\TransactionResource;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TransactionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_relations_and_resource_shape(): void
    {
        $branch = Branch::create(['name' => 'Pusat', 'address' => 'Jakarta']);
        $currency = Currency::create(['code' => 'USD']);
        $employee = Employee::create(['branch_id' => $branch->id, 'name' => 'Dewi', 'position' => 'Teller']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret']);
        $customer = Customer::create([
            'name' => 'Andi Wijaya',
            'identity_number' => '3171234567890001',
            'address' => 'Jl. Sudirman',
            'phone' => '081234567890',
            'created_by' => $user->id,
        ]);

        $transaction = Transaction::create([
            'transaction_number' => 'TRX-TEST-001',
            'branch_id' => $branch->id,
            'type' => 'buy',
            'currency_id' => $currency->id,
            'amount' => 500,
            'rate_default' => 15750,
            'rate_actual' => 15780,
            'total_amount' => 7890000,
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'requires_review' => false,
        ]);

        $loaded = Transaction::with(['branch', 'currency', 'customer', 'employee', 'user'])->findOrFail($transaction->id);

        $this->assertSame('Pusat', $loaded->branch->name);
        $this->assertSame('USD', $loaded->currency->code);
        $this->assertSame('Andi Wijaya', $loaded->customer->name);
        $this->assertSame('3171234567890001', $loaded->customer->identity_number);
        $this->assertSame('Dewi', $loaded->employee->name);
        $this->assertSame('admin@test.local', $loaded->user->email);
        $this->assertEquals(7890000, $loaded->total_amount);

        $resource = (new TransactionResource($loaded))->toArray(Request::create('/'));

        $this->assertSame('TRX-TEST-001', $resource['transaction_number']);
        $this->assertSame('USD', $resource['currency_code']);
        $this->assertSame('Andi Wijaya', $resource['customer_name']);
        $this->assertSame('Dewi', $resource['employee_name']);
        $this->assertFalse($resource['requires_review']);
    }
}
