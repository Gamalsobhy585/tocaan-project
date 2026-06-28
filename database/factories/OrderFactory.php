<?php

namespace Database\Factories;

use App\Enums\OrderStatusEnum;
use App\Models\Currency;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_number' => 'ORD-' . strtoupper((string) Str::ulid()),
            'currency_id'  => Currency::factory(),
            'status'       => OrderStatusEnum::Pending,
            'total_amount' => $this->faker->randomFloat(2, 10, 10000),
            'notes'        => $this->faker->optional()->sentence(),
            'created_by'   => User::factory(),
            'updated_by'   => User::factory(),
            'cancelled_by' => null,
            'cancelled_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => OrderStatusEnum::Pending]);
    }

    public function processing(): static
    {
        return $this->state(['status' => OrderStatusEnum::Confirmed]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status'       => OrderStatusEnum::Cancelled,
            'cancelled_by' => User::factory(),
            'cancelled_at' => now(),
        ]);
    }
}