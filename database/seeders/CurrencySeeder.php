<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    private const RATES = [
        'USD' => ['buy' => 15750, 'sell' => 15850],
        'SGD' => ['buy' => 11600, 'sell' => 11750],
        'EUR' => ['buy' => 16900, 'sell' => 17100],
        'JPY' => ['buy' => 103, 'sell' => 106],
        'AUD' => ['buy' => 10200, 'sell' => 10400],
    ];

    public function run(): void
    {
        foreach (self::RATES as $code => $rate) {
            $currency = Currency::firstOrCreate(['code' => $code]);

            DB::table('exchange_rates')->insert([
                'currency_id' => $currency->id,
                'rate_buy' => $rate['buy'],
                'rate_sell' => $rate['sell'],
                'effective_date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
