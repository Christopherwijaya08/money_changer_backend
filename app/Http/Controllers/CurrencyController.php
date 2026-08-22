<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCurrencyRequest;
use App\Http\Resources\CurrencyResource;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(Request $request)
    {
        $currencies = Currency::query()
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('code')
            ->get();

        return CurrencyResource::collection($currencies);
    }

    public function store(StoreCurrencyRequest $request)
    {
        $data = $request->validated();
        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $data['is_active'] ?? true;

        $currency = Currency::create($data);

        return new CurrencyResource($currency);
    }

    public function show(Currency $currency)
    {
        return new CurrencyResource($currency);
    }

    public function update(StoreCurrencyRequest $request, Currency $currency)
    {
        $data = $request->validated();
        $data['code'] = strtoupper($data['code']);
        if (! $request->has('is_active')) {
            unset($data['is_active']);
        }

        $currency->update($data);

        return new CurrencyResource($currency);
    }

    public function destroy(Currency $currency)
    {
        if ($currency->transactions()->exists() || $currency->exchangeRates()->exists()) {
            return response()->json([
                'message' => 'Mata uang ini sudah punya riwayat transaksi/kurs dan tidak bisa dihapus. Nonaktifkan saja.',
            ], 409);
        }

        $currency->delete();

        return response()->noContent();
    }
}
