<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfitLossResource;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function profitLoss(Request $request)
    {
        $transactions = Transaction::query()
            ->with('currency')
            ->when($request->query('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->query('currency_id'), fn ($q, $id) => $q->where('currency_id', $id))
            ->when($request->query('date_from'), fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->query('date_to'), fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest()
            ->get();

        $totalMargin = $transactions->sum(fn (Transaction $t) => $t->margin());

        return ProfitLossResource::collection($transactions)->additional(['total_margin' => $totalMargin]);
    }
}
