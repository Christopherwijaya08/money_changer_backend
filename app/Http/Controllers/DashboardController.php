<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
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

    public function trend(Request $request)
    {
        $branchId = $request->query('branch_id');
        $period = $request->integer('period', 7);
        $endDate = Carbon::parse($request->query('end_date', now()->toDateString()));
        $startDate = $endDate->copy()->subDays($period - 1);

        $transactionsByDate = Transaction::query()
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->get()
            ->groupBy(fn (Transaction $t) => $t->created_at->toDateString());

        $data = collect();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->toDateString();
            $data->push([
                'date' => $dateString,
                'omzet' => $transactionsByDate->get($dateString, collect())->sum('total_amount'),
            ]);
        }

        return response()->json(['data' => $data->values()]);
    }
}
