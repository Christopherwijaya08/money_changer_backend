<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfitLossResource;
use App\Models\Employee;
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

    public function employeePerformance(Request $request)
    {
        $branchId = $request->query('branch_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $rows = Employee::query()
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->orderBy('name')
            ->get()
            ->map(function (Employee $employee) use ($branchId, $dateFrom, $dateTo) {
                $transactions = Transaction::where('employee_id', $employee->id)
                    ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
                    ->when($dateFrom, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                    ->when($dateTo, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
                    ->get();

                return [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'transaction_count' => $transactions->count(),
                    'total_omzet' => $transactions->sum('total_amount'),
                    'total_margin' => $transactions->sum(fn (Transaction $t) => $t->margin()),
                ];
            })
            ->values();

        return response()->json(['data' => $rows]);
    }
}
