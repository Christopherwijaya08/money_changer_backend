<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $branchId = $request->query('branch_id');
        $date = $request->query('date', now()->toDateString());

        $transactions = Transaction::query()
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereDate('created_at', $date)
            ->get();

        return response()->json([
            'date' => $date,
            'omzet' => $transactions->sum('total_amount'),
            'transaction_count' => $transactions->count(),
        ]);
    }
}
