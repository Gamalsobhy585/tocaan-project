<?php

namespace Database\Seeders;

use App\Enums\OrderStatusEnum;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use App\Modules\Order\Services\Interfaces\IOrderService;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        if (! $user) {
            $this->command?->warn(
                'OrderSeeder skipped: no users found.'
            );

            return;
        }

        $currencyIds = Currency::query()
            ->where('is_active', true)
            ->pluck('id');

        if ($currencyIds->isEmpty()) {
            $this->command?->warn(
                'OrderSeeder skipped: no active currencies found.'
            );

            return;
        }

        /** @var IOrderService $service */
        $service = app(IOrderService::class);

        for ($index = 0; $index < 10; $index++) {
            $products = Product::query()
                ->where('quantity_in_stock', '>', 0)
                ->inRandomOrder()
                ->limit(fake()->numberBetween(1, 3))
                ->get();

            if ($products->isEmpty()) {
                break;
            }

            $order = $service->create(
                data: [
                    'currency_id' => $currencyIds->random(),
                    'notes' => fake()->optional()->sentence(),

                    'items' => $products
                        ->map(fn (Product $product): array => [
                            'product_id' => $product->id,
                            'quantity' => 1,
                        ])
                        ->values()
                        ->all(),
                ],
                actorId: $user->id
            );

            if (fake()->boolean(50)) {
                $service->update(
                    orderId: $order->id,
                    data: [
                        'status' =>
                            OrderStatusEnum::Confirmed->value,
                    ],
                    actorId: $user->id
                );
            }
        }
    }
}