<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateExchangeRateRequest;
use App\Http\Resources\ExchangeRateHistoryResource;
use App\Http\Resources\ExchangeRateResource;
use App\Models\Currency;
use App\Models\ExchangeRateHistory;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function index()
    {
        $currencies = Currency::with(['latestExchangeRate.createdBy'])->orderBy('code')->get();

        return ExchangeRateResource::collection($currencies);
    }

    public function update(UpdateExchangeRateRequest $request, Currency $currency)
    {
        $data = $request->validated();
        $previous = $currency->latestExchangeRate;

        $rate = $currency->exchangeRates()->whereDate('effective_date', now()->toDateString())->first();
        $attributes = [
            'rate_buy' => $data['rate_buy'],
            'rate_sell' => $data['rate_sell'],
            'created_by' => $data['user_id'],
        ];

        if ($rate) {
            $rate->update($attributes);
        } else {
            $rate = $currency->exchangeRates()->create([
                ...$attributes,
                'effective_date' => now()->toDateString(),
            ]);
        }

        if ($previous) {
            ExchangeRateHistory::create([
                'exchange_rate_id' => $rate->id,
                'old_buy' => $previous->rate_buy,
                'old_sell' => $previous->rate_sell,
                'new_buy' => $data['rate_buy'],
                'new_sell' => $data['rate_sell'],
                'changed_by' => $data['user_id'],
            ]);
        }

        return new ExchangeRateResource($currency->load('latestExchangeRate.createdBy'));
    }

    public function history(Request $request, Currency $currency)
    {
        $history = ExchangeRateHistory::whereHas('exchangeRate', fn ($q) => $q->where('currency_id', $currency->id))
            ->with('changedBy')
            ->latest('changed_at')
            ->paginate($request->integer('per_page', 15));

        return ExchangeRateHistoryResource::collection($history);
    }
}
