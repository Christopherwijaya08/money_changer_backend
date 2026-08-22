<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfitLossResource;
use App\Models\Employee;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function profitLoss(Request $request)
    {
        $transactions = $this->profitLossTransactions($request);
        $totalMargin = $transactions->sum(fn (Transaction $t) => $t->margin());

        return ProfitLossResource::collection($transactions)->additional(['total_margin' => $totalMargin]);
    }

    public function profitLossExport(Request $request)
    {
        $transactions = $this->profitLossTransactions($request);
        $headers = ['No. Transaksi', 'Tanggal', 'Tipe', 'Mata Uang', 'Nominal', 'Kurs Default', 'Kurs Aktual', 'Laba/Rugi'];
        $rows = $transactions->map(fn (Transaction $t) => [
            $t->transaction_number,
            $t->created_at,
            $t->type === 'buy' ? 'Beli' : 'Jual',
            $t->currency?->code,
            $t->amount,
            $t->rate_default,
            $t->rate_actual,
            $t->margin(),
        ]);

        return $this->export($request, 'Laporan Laba-Rugi', $headers, $rows, 'laporan-laba-rugi');
    }

    public function employeePerformance(Request $request)
    {
        return response()->json(['data' => $this->employeePerformanceRows($request)]);
    }

    public function employeePerformanceExport(Request $request)
    {
        $headers = ['Nama Karyawan', 'Jumlah Transaksi', 'Total Omzet', 'Total Laba/Rugi'];
        $rows = $this->employeePerformanceRows($request)->map(fn (array $r) => [
            $r['employee_name'], $r['transaction_count'], $r['total_omzet'], $r['total_margin'],
        ]);

        return $this->export($request, 'Laporan per Karyawan', $headers, $rows, 'laporan-per-karyawan');
    }

    private function profitLossTransactions(Request $request): Collection
    {
        return Transaction::query()
            ->with('currency')
            ->when($request->query('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->query('currency_id'), fn ($q, $id) => $q->where('currency_id', $id))
            ->when($request->query('date_from'), fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->query('date_to'), fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest()
            ->get();
    }

    private function employeePerformanceRows(Request $request): Collection
    {
        $branchId = $request->query('branch_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        return Employee::query()
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
    }

    private function export(Request $request, string $title, array $headers, Collection $rows, string $filenameBase)
    {
        if ($request->query('format') === 'pdf') {
            return Pdf::loadView('reports.table', compact('title', 'headers', 'rows'))
                ->download("{$filenameBase}.pdf");
        }

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, "{$filenameBase}.csv", ['Content-Type' => 'text/csv']);
    }
}
