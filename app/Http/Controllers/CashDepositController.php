<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashDepositRequest;
use App\Http\Resources\CashDepositResource;
use App\Models\CashDeposit;
use Illuminate\Http\Request;

class CashDepositController extends Controller
{
    public function index(Request $request)
    {
        $deposits = CashDeposit::query()
            ->with(['branch', 'currency', 'createdBy'])
            ->when($request->query('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->query('currency_id'), fn ($q, $id) => $q->where('currency_id', $id))
            ->when($request->query('date'), fn ($q, $date) => $q->whereDate('created_at', $date))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return CashDepositResource::collection($deposits);
    }

    public function store(StoreCashDepositRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $data['user_id'];
        unset($data['user_id']);

        $deposit = CashDeposit::create($data);

        return new CashDepositResource($deposit->load(['branch', 'currency', 'createdBy']));
    }
}
