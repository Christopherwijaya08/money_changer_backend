<?php

namespace App\Http\Controllers;

use App\Models\CashDeposit;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Http\Request;

class CashBalanceController extends Controller
{
    // Saldo real-time: dihitung dari akumulasi setor kas + transaksi beli - transaksi jual,
    // bukan disimpan di kolom terpisah, supaya selalu konsisten dengan data sumbernya.
    public function index(Request $request)
    {
        $branchId = $request->query('branch_id');

        $balances = Currency::query()
            ->orderBy('code')
            ->get()
            ->map(function (Currency $currency) use ($branchId) {
                $depositSum = CashDeposit::where('currency_id', $currency->id)
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->sum('amount');

                $buySum = Transaction::where('currency_id', $currency->id)
                    ->where('type', 'buy')
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->sum('amount');

                $sellSum = Transaction::where('currency_id', $currency->id)
                    ->where('type', 'sell')
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->sum('amount');

                return [
                    'currency_id' => $currency->id,
                    'currency_code' => $currency->code,
                    'currency_name' => $currency->name,
                    'balance' => $depositSum + $buySum - $sellSum,
                ];
            })
            ->values();

        return response()->json(['data' => $balances]);
    }
}
