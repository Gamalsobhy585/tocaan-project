<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $productNumber = $this->faker->unique()->numberBetween(1000, 999999);

        return [
            'name_ar' => 'منتج رقم ' . $productNumber,

            'name_en' => 'Product ' . $productNumber,

            'code' => strtoupper(
                $this->faker->unique()->bothify('PRD-####-??')
            ),

            'quantity_in_stock' => $this->faker->numberBetween(0, 500),

            'unit_price' => $this->faker->randomFloat(
                2,
                10,
                5000
            ),
        ];
    }
}