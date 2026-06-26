<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Modules\Payment\Services\Interfaces\IPaymentService;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        if (! $user) {
            $this->command?->warn(
                'PaymentSeeder skipped: no users found.'
            );

            return;
        }

        $methods = PaymentMethod::query()
            ->where('is_active', true)
            ->get();

        if ($methods->isEmpty()) {
            $this->command?->warn(
                'PaymentSeeder skipped: no active payment methods found.'
            );

            return;
        }

        $orders = Order::query()
            ->whereDoesntHave('payments')
            ->limit(10)
            ->get();

        if ($orders->isEmpty()) {
            $this->command?->warn(
                'PaymentSeeder skipped: no payable orders found.'
            );

            return;
        }

        /** @var IPaymentService $service */
        $service = app(IPaymentService::class);

        foreach ($orders as $index => $order) {
            $method = $methods[$index % $methods->count()];

            $service->process(
                orderId: $order->id,
                data: [
                    'payment_method_id' => $method->id,
                    'gateway_payload' => [
                        'simulate_success' => $index % 3 !== 0,
                    ],
                ],
                actorId: $user->id
            );
        }
    }
}