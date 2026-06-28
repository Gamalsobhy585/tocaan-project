<?php

namespace Tests\Unit\Modules\Order;

use App\Enums\OrderHistoryActionEnum;
use App\Enums\OrderStatusEnum;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Modules\Order\Repositories\Implementation\OrderRepository;
use App\Modules\Order\Repositories\Interfaces\IOrderRepository;
use App\Modules\Order\Services\OrderService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;
    private User $user;
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new OrderService(
            new OrderRepository()
        );

        $this->user = User::factory()->create();

        $this->currency = Currency::factory()->create([
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createOrder(array $overrides = []): Order
    {
        return Order::factory()->create(array_merge([
            'currency_id' => $this->currency->id,
            'status' => OrderStatusEnum::Pending,
            'total_amount' => 0,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ], $overrides));
    }

    private function createProduct(
        int $stock = 10,
        float $price = 50.00
    ): Product {
        return Product::factory()->create([
            'quantity_in_stock' => $stock,
            'unit_price' => $price,
        ]);
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    #[Test]
    public function index_delegates_to_repository_paginate(): void
    {
        /** @var IOrderRepository&MockInterface $repository */
        $repository = Mockery::mock(IOrderRepository::class);

        $service = new OrderService($repository);

        $filters = [
            'per_page' => 10,
        ];

        $paginator = Mockery::mock(
            LengthAwarePaginator::class
        );

        $repository
            ->shouldReceive('paginate')
            ->once()
            ->with($filters)
            ->andReturn($paginator);

        $result = $service->index($filters);

        $this->assertSame($paginator, $result);
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    #[Test]
    public function create_calculates_total_amount_correctly(): void
    {
        $productA = $this->createProduct(
            stock: 10,
            price: 100.00
        );

        $productB = $this->createProduct(
            stock: 5,
            price: 50.00
        );

        $order = $this->service->create([
            'currency_id' => $this->currency->id,
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 2,
                ],
                [
                    'product_id' => $productB->id,
                    'quantity' => 3,
                ],
            ],
        ], actorId: $this->user->id);

        $this->assertEquals(
            350.00,
            (float) $order->total_amount
        );

        $this->assertSame(
            OrderStatusEnum::Pending,
            $order->status
        );

        $this->assertStringStartsWith(
            'ORD-',
            $order->order_number
        );

        $this->assertEquals(
            8,
            $productA->fresh()->quantity_in_stock
        );

        $this->assertEquals(
            2,
            $productB->fresh()->quantity_in_stock
        );

        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => OrderHistoryActionEnum::Created->value,
            'old_status' => null,
            'new_status' => OrderStatusEnum::Pending->value,
            'performed_by' => $this->user->id,
        ]);
    }

    #[Test]
    public function create_throws_validation_exception_when_stock_is_insufficient(): void
    {
        $product = $this->createProduct(
            stock: 1,
            price: 10.00
        );

        try {
            $this->service->create([
                'currency_id' => $this->currency->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 99,
                    ],
                ],
            ], actorId: $this->user->id);

            $this->fail(
                'Expected a ValidationException to be thrown.'
            );
        } catch (ValidationException $exception) {
            $this->assertSame(
                "Product {$product->code} has only 1 items in stock.",
                $exception->errors()['items'][0]
            );
        }

        $this->assertDatabaseCount('orders', 0);

        $this->assertEquals(
            1,
            $product->fresh()->quantity_in_stock
        );
    }

    #[Test]
    public function create_throws_validation_exception_for_unknown_product(): void
    {
        try {
            $this->service->create([
                'currency_id' => $this->currency->id,
                'items' => [
                    [
                        'product_id' => 9999,
                        'quantity' => 1,
                    ],
                ],
            ], actorId: $this->user->id);

            $this->fail(
                'Expected a ValidationException to be thrown.'
            );
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Product 9999 does not exist.',
                $exception->errors()['items'][0]
            );
        }

        $this->assertDatabaseCount('orders', 0);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    #[Test]
    public function update_throws_when_order_is_cancelled(): void
    {
        $order = $this->createOrder([
            'status' => OrderStatusEnum::Cancelled,
            'notes' => 'Original note',
        ]);

        try {
            $this->service->update(
                $order->id,
                [
                    'notes' => 'This must fail',
                ],
                actorId: $this->user->id
            );

            $this->fail(
                'Expected a ValidationException to be thrown.'
            );
        } catch (ValidationException $exception) {
            $this->assertSame(
                'A cancelled order cannot be updated.',
                $exception->errors()['order'][0]
            );
        }

        $this->assertSame(
            'Original note',
            $order->fresh()->notes
        );
    }

    #[Test]
    public function update_records_old_and_new_status_in_history(): void
    {
        $order = $this->createOrder([
            'status' => OrderStatusEnum::Pending,
        ]);

        $updatedOrder = $this->service->update(
            $order->id,
            [
                'status' => OrderStatusEnum::Confirmed->value,
            ],
            actorId: $this->user->id
        );

        $this->assertSame(
            OrderStatusEnum::Confirmed,
            $updatedOrder->status
        );

        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => OrderHistoryActionEnum::Updated->value,
            'old_status' => OrderStatusEnum::Pending->value,
            'new_status' => OrderStatusEnum::Confirmed->value,
            'performed_by' => $this->user->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    #[Test]
    public function delete_restores_stock_and_soft_deletes_order(): void
    {
        $product = $this->createProduct(
            stock: 5,
            price: 10.00
        );

        $order = $this->createOrder([
            'status' => OrderStatusEnum::Pending,
            'total_amount' => 50.00,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 10.00,
            'line_total' => 50.00,
        ]);

        $cancelledOrder = $this->service->delete(
            $order->id,
            actorId: $this->user->id
        );

        $this->assertSame(
            OrderStatusEnum::Cancelled,
            $cancelledOrder->status
        );

        $this->assertEquals(
            10,
            $product->fresh()->quantity_in_stock
        );

        $this->assertSoftDeleted('orders', [
            'id' => $order->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatusEnum::Cancelled->value,
            'cancelled_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => OrderHistoryActionEnum::Cancelled->value,
            'old_status' => OrderStatusEnum::Pending->value,
            'new_status' => OrderStatusEnum::Cancelled->value,
            'performed_by' => $this->user->id,
        ]);
    }
}
