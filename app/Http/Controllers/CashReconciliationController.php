<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashReconciliationRequest;
use App\Http\Resources\CashReconciliationResource;
use App\Models\CashDeposit;
use App\Models\CashReconciliation;
use App\Models\Currency;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->query('branch_id');
        $date = $request->query('date', now()->toDateString());

        $rows = Currency::query()
            ->orderBy('code')
            ->get()
            ->map(function (Currency $currency) use ($branchId, $date) {
                $computed = $this->computeBalances($currency->id, $branchId, $date);

                $existing = CashReconciliation::where('currency_id', $currency->id)
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->whereDate('date', $date)
                    ->first();

                return [
                    'currency_id' => $currency->id,
                    'currency_code' => $currency->code,
                    ...$computed,
                    'physical_balance' => $existing?->physical_balance,
                    'difference' => $existing?->difference,
                ];
            })
            ->values();

        return response()->json(['data' => $rows]);
    }

    public function store(StoreCashReconciliationRequest $request)
    {
        $data = $request->validated();
        $computed = $this->computeBalances($data['currency_id'], $data['branch_id'] ?? null, $data['date']);
        $difference = $data['physical_balance'] - $computed['system_balance'];

        $attributes = [
            ...$computed,
            'physical_balance' => $data['physical_balance'],
            'difference' => $difference,
            'created_by' => $data['user_id'],
        ];

        $reconciliation = CashReconciliation::where('currency_id', $data['currency_id'])
            ->where('branch_id', $data['branch_id'] ?? null)
            ->whereDate('date', $data['date'])
            ->first();

        if ($reconciliation) {
            $reconciliation->update($attributes);
        } else {
            $reconciliation = CashReconciliation::create([
                ...$attributes,
                'branch_id' => $data['branch_id'] ?? null,
                'currency_id' => $data['currency_id'],
                'date' => $data['date'],
            ]);
        }

        return new CashReconciliationResource($reconciliation->load('currency'));
    }

    private function computeBalances(int $currencyId, ?int $branchId, string $date): array
    {
        $balanceUpTo = function (string $upToDate) use ($currencyId, $branchId) {
            $depositSum = CashDeposit::where('currency_id', $currencyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', '<=', $upToDate)
                ->sum('amount');

            $buySum = Transaction::where('currency_id', $currencyId)
                ->where('type', 'buy')
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', '<=', $upToDate)
                ->sum('amount');

            $sellSum = Transaction::where('currency_id', $currencyId)
                ->where('type', 'sell')
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', '<=', $upToDate)
                ->sum('amount');

            return $depositSum + $buySum - $sellSum;
        };

        $previousDay = Carbon::parse($date)->subDay()->toDateString();

        $cashIn = CashDeposit::where('currency_id', $currencyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', $date)
            ->sum('amount')
            + Transaction::where('currency_id', $currencyId)
                ->where('type', 'buy')
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', $date)
                ->sum('amount');

        $cashOut = Transaction::where('currency_id', $currencyId)
            ->where('type', 'sell')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', $date)
            ->sum('amount');

        return [
            'opening_balance' => $balanceUpTo($previousDay),
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'system_balance' => $balanceUpTo($date),
        ];
    }
}
