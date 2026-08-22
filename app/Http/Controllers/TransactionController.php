<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = Transaction::query()
            ->with(['branch', 'currency', 'customer', 'employee', 'user'])
            ->when($request->query('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->query('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->query('currency_id'), fn ($q, $id) => $q->where('currency_id', $id))
            ->when($request->query('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))
            ->when($request->query('date'), fn ($q, $date) => $q->whereDate('created_at', $date))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return TransactionResource::collection($transactions);
    }

    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction->load(['branch', 'currency', 'customer', 'employee', 'user']));
    }

    public function store(StoreTransactionRequest $request)
    {
        $data = $request->validated();
        $totalAmount = $data['amount'] * $data['rate_actual'];

        $transaction = Transaction::create([
            ...$data,
            'transaction_number' => $this->generateTransactionNumber(),
            'total_amount' => $totalAmount,
            'requires_review' => $totalAmount > Setting::current()->review_threshold,
        ]);

        return new TransactionResource($transaction->load(['branch', 'currency', 'customer', 'employee', 'user']));
    }

    private function generateTransactionNumber(): string
    {
        $today = now()->format('Ymd');
        $countToday = Transaction::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('TRX-%s-%03d', $today, $countToday);
    }
}
