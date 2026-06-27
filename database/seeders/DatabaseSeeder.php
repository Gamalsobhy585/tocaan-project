<?php

namespace Database\Seeders;

use App\Modules\Authentication\Seeders\UserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CurrencySeeder::class,
            ProductSeeder::class,
            PaymentMethodSeeder::class,



        ]);
    }
}