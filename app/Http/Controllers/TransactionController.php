<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\AuditLog;
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
            ->when($request->has('requires_review'), fn ($q) => $q->where('requires_review', $request->boolean('requires_review')))
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
            'user_id' => $request->user()->id,
            'transaction_number' => $this->generateTransactionNumber(),
            'total_amount' => $totalAmount,
            'requires_review' => $totalAmount > Setting::current()->review_threshold,
        ]);

        return new TransactionResource($transaction->load(['branch', 'currency', 'customer', 'employee', 'user']));
    }

    public function update(StoreTransactionRequest $request, Transaction $transaction)
    {
        $data = $request->validated();
        $before = $transaction->only(['amount', 'rate_actual']);
        $totalAmount = $data['amount'] * $data['rate_actual'];

        $transaction->update([
            ...$data,
            'total_amount' => $totalAmount,
            'requires_review' => $totalAmount > Setting::current()->review_threshold,
        ]);

        $changes = [];
        if ((float) $before['amount'] !== (float) $data['amount']) {
            $changes[] = sprintf(
                'nominal %s → %s',
                number_format($before['amount'], 0, ',', '.'),
                number_format($data['amount'], 0, ',', '.'),
            );
        }
        if ((float) $before['rate_actual'] !== (float) $data['rate_actual']) {
            $changes[] = sprintf(
                'kurs aktual %s → %s',
                number_format($before['rate_actual'], 0, ',', '.'),
                number_format($data['rate_actual'], 0, ',', '.'),
            );
        }
        $description = $transaction->transaction_number.': '.($changes ? implode(', ', $changes) : 'data diperbarui');

        AuditLog::record($request->user()->id, 'transaction_edit', $description);

        return new TransactionResource($transaction->load(['branch', 'currency', 'customer', 'employee', 'user']));
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        AuditLog::record($request->user()->id, 'transaction_delete', "{$transaction->transaction_number} dihapus");

        $transaction->delete();

        return response()->noContent();
    }

    private function generateTransactionNumber(): string
    {
        $today = now()->format('Ymd');
        $countToday = Transaction::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('TRX-%s-%03d', $today, $countToday);
    }
}
