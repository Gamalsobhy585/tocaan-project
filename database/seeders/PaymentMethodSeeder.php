<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name_ar' => 'بطاقة ائتمان',
                'name_en' => 'Credit Card',
                'code' => 'credit_card',
                'strategy_key' => 'credit_card',
                'is_active' => true,
            ],
            [
                'name_ar' => 'باي بال',
                'name_en' => 'PayPal',
                'code' => 'paypal',
                'strategy_key' => 'paypal',
                'is_active' => true,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}