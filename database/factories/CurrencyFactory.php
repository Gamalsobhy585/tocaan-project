<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    /**
     * Codes already inserted by CurrencySeeder — exclude from random generation
     * to avoid unique-constraint collisions when the seeder runs in tests.
     */
    private static array $reservedCodes = ['USD', 'EGP', 'EUR', 'GBP'];

    public function definition(): array
    {
        return [
            'name_ar'   => $this->faker->word() . ' عربي',
            'name_en'   => $this->faker->word(),
            'code'      => $this->uniqueSafeCode(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    /**
     * Generates a unique 3-letter code that doesn't clash with seeded codes.
     */
    private function uniqueSafeCode(): string
    {
        return $this->faker->unique()->regexify('(?!USD|EGP|EUR|GBP)[A-Z]{3}');
    }
}   