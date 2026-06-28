<?php

namespace Tests\Unit\Modules\Order;

use App\Enums\OrderStatusEnum;
use App\Models\Currency;
use App\Models\Order;
use App\Models\User;
use App\Modules\Order\Repositories\Implementation\OrderRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private OrderRepository $repository;
    private User $user;
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new OrderRepository();
        $this->user = User::factory()->create();
        $this->currency = Currency::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createOrder(array $overrides = []): Order
    {
        return Order::factory()->create(array_merge([
            'currency_id' => $this->currency->id,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'status' => OrderStatusEnum::Pending,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // paginate
    // -------------------------------------------------------------------------

    #[Test]
    public function paginate_returns_all_orders_by_default(): void
    {
        $this->createOrder();
        $this->createOrder();

        $result = $this->repository->paginate([]);

        $this->assertEquals(2, $result->total());
    }

    #[Test]
    public function paginate_filters_by_status(): void
    {
        $this->createOrder([
            'status' => OrderStatusEnum::Pending,
        ]);

        $this->createOrder([
            'status' => OrderStatusEnum::Confirmed,
        ]);

        $result = $this->repository->paginate([
            'status' => OrderStatusEnum::Pending->value,
        ]);

        $this->assertEquals(1, $result->total());

        $this->assertSame(
            OrderStatusEnum::Pending,
            $result->items()[0]->status
        );
    }

    #[Test]
    public function paginate_searches_by_order_number(): void
    {
        $this->createOrder([
            'order_number' => 'ORD-ALPHA',
        ]);

        $this->createOrder([
            'order_number' => 'ORD-BETA',
        ]);

        $result = $this->repository->paginate([
            'search' => 'ALPHA',
        ]);

        $this->assertEquals(1, $result->total());

        $this->assertEquals(
            'ORD-ALPHA',
            $result->items()[0]->order_number
        );
    }

    #[Test]
    public function paginate_respects_per_page(): void
    {
        $this->createOrder();
        $this->createOrder();
        $this->createOrder();

        $result = $this->repository->paginate([
            'per_page' => 2,
        ]);

        $this->assertCount(2, $result->items());
        $this->assertEquals(3, $result->total());
    }

    #[Test]
    public function paginate_includes_cancelled_orders_when_filtering_by_cancelled_status(): void
    {
        $order = $this->createOrder([
            'status' => OrderStatusEnum::Cancelled,
        ]);

        $order->delete();

        $result = $this->repository->paginate([
            'status' => OrderStatusEnum::Cancelled->value,
        ]);

        $this->assertEquals(1, $result->total());
    }

    #[Test]
    public function paginate_eager_loads_currency_and_items(): void
    {
        $this->createOrder();

        $result = $this->repository->paginate([]);

        $order = $result->items()[0];

        $this->assertTrue(
            $order->relationLoaded('currency')
        );

        $this->assertTrue(
            $order->relationLoaded('items')
        );
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    #[Test]
    public function create_persists_order_and_returns_model(): void
    {
        $order = $this->repository->create([
            'order_number' => 'ORD-001',
            'currency_id' => $this->currency->id,
            'status' => OrderStatusEnum::Pending,
            'total_amount' => 250.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertNotNull($order->id);

        $this->assertDatabaseHas('orders', [
            'order_number' => 'ORD-001',
        ]);
    }

    // -------------------------------------------------------------------------
    // findForUpdate
    // -------------------------------------------------------------------------

    #[Test]
    public function find_for_update_returns_order_with_items(): void
    {
        $order = $this->createOrder();

        $result = $this->repository->findForUpdate(
            $order->id
        );

        $this->assertEquals(
            $order->id,
            $result->id
        );

        $this->assertTrue(
            $result->relationLoaded('items')
        );
    }

    #[Test]
    public function find_for_update_throws_model_not_found_for_missing_id(): void
    {
        $this->expectException(
            ModelNotFoundException::class
        );

        $this->repository->findForUpdate(99999);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    #[Test]
    public function update_persists_changes_and_returns_refreshed_model(): void
    {
        $order = $this->createOrder([
            'notes' => 'original',
        ]);

        $updated = $this->repository->update(
            $order,
            [
                'notes' => 'changed',
            ]
        );

        $this->assertEquals(
            'changed',
            $updated->notes
        );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'notes' => 'changed',
        ]);
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    #[Test]
    public function delete_soft_deletes_the_order(): void
    {
        $order = $this->createOrder();

        $this->repository->delete($order);

        $this->assertSoftDeleted('orders', [
            'id' => $order->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // loadDetails
    // -------------------------------------------------------------------------

    #[Test]
    public function load_details_eager_loads_all_relations(): void
    {
        $order = $this->createOrder();

        $result = $this->repository->loadDetails($order);

        $this->assertTrue(
            $result->relationLoaded('currency')
        );

        $this->assertTrue(
            $result->relationLoaded('items')
        );

        $this->assertTrue(
            $result->relationLoaded('histories')
        );

        $this->assertTrue(
            $result->relationLoaded('creator')
        );

        $this->assertTrue(
            $result->relationLoaded('updater')
        );

        $this->assertTrue(
            $result->relationLoaded('canceller')
        );
    }
}
