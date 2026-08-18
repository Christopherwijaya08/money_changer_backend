<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExchangeRateResource;
use App\Models\Currency;

class ExchangeRateController extends Controller
{
    public function index()
    {
        $currencies = Currency::with(['latestExchangeRate.createdBy'])->orderBy('code')->get();

        return ExchangeRateResource::collection($currencies);
    }
}
