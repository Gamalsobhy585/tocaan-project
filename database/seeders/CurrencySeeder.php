<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            [
                'name_ar' => 'الدولار الأمريكي',
                'name_en' => 'US Dollar',
                'code' => 'USD',
                'is_active' => true,
            ],
            [
                'name_ar' => 'الجنيه المصري',
                'name_en' => 'Egyptian Pound',
                'code' => 'EGP',
                'is_active' => true,
            ],
            [
                'name_ar' => 'اليورو',
                'name_en' => 'Euro',
                'code' => 'EUR',
                'is_active' => true,
            ],
            [
                'name_ar' => 'الجنيه الإسترليني',
                'name_en' => 'British Pound',
                'code' => 'GBP',
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::query()->updateOrCreate(
                [
                    'code' => $currency['code'],
                ],
                $currency
            );
        }
    }
}