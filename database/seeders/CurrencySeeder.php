<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    private const RATES = [
        'USD' => ['name' => 'Dolar Amerika Serikat', 'buy' => 15750, 'sell' => 15850, 'is_active' => true],
        'SGD' => ['name' => 'Dolar Singapura', 'buy' => 11600, 'sell' => 11750, 'is_active' => true],
        'EUR' => ['name' => 'Euro', 'buy' => 16900, 'sell' => 17100, 'is_active' => true],
        'JPY' => ['name' => 'Yen Jepang', 'buy' => 103, 'sell' => 106, 'is_active' => true],
        'AUD' => ['name' => 'Dolar Australia', 'buy' => 10200, 'sell' => 10400, 'is_active' => false],
    ];

    public function run(): void
    {
        foreach (self::RATES as $code => $rate) {
            $currency = Currency::firstOrCreate(
                ['code' => $code],
                ['name' => $rate['name'], 'is_active' => $rate['is_active']]
            );

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
