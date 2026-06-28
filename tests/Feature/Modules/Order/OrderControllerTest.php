<?php

namespace Tests\Feature\Modules\Order;

use App\Enums\OrderHistoryActionEnum;
use App\Enums\OrderStatusEnum;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->token = JWTAuth::fromUser(
            $this->user
        );

        $this->currency = Currency::factory()->create([
            'is_active' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function auth(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}",
        ];
    }

    private function makeProduct(
        int $stock = 100,
        float $price = 50.00
    ): Product {
        return Product::factory()->create([
            'quantity_in_stock' => $stock,
            'unit_price' => $price,
        ]);
    }

    private function validOrderPayload(
        array $overrides = []
    ): array {
        $product = $this->makeProduct(
            stock: 10,
            price: 100.00
        );

        return array_merge([
            'currency_id' => $this->currency->id,
            'notes' => 'Test order',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_paginated_orders(): void
    {
        Order::factory(3)->create([
            'currency_id' => $this->currency->id,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->getJson(
            route('orders.index'),
            $this->auth()
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'order_number',
                        'status',
                        'total_amount',
                    ],
                ],
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    #[Test]
    public function it_filters_orders_by_status(): void
    {
        Order::factory()->create([
            'currency_id' => $this->currency->id,
            'status' => OrderStatusEnum::Pending,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->getJson(
            route('orders.index', [
                'status' => OrderStatusEnum::Pending->value,
            ]),
            $this->auth()
        );

        $response->assertOk();

        collect($response->json('data'))->each(
            fn (array $order) => $this->assertEquals(
                OrderStatusEnum::Pending->value,
                $order['status']['value']
            )
        );
    }

    #[Test]
    public function it_searches_orders_by_order_number(): void
    {
        Order::factory()->create([
            'currency_id' => $this->currency->id,
            'order_number' => 'ORD-FINDME',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        Order::factory()->create([
            'currency_id' => $this->currency->id,
            'order_number' => 'ORD-OTHER',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->getJson(
            route('orders.index', [
                'search' => 'FINDME',
            ]),
            $this->auth()
        );

        $response->assertOk();

        $this->assertCount(
            1,
            $response->json('data')
        );

        $this->assertEquals(
            'ORD-FINDME',
            $response->json('data.0.order_number')
        );
    }

    #[Test]
    public function it_returns_401_without_token_on_index(): void
    {
        $this->getJson(
            route('orders.index')
        )->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    #[Test]
    public function it_creates_an_order_and_decrements_stock(): void
    {
        $product = $this->makeProduct(
            stock: 10,
            price: 100.00
        );

        $response = $this->postJson(
            route('orders.store'),
            [
                'currency_id' => $this->currency->id,
                'notes' => 'My order',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 3,
                    ],
                ],
            ],
            $this->auth()
        );

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'total_amount',
                    'items',
                ],
            ])
            ->assertJsonPath(
                'data.status.value',
                OrderStatusEnum::Pending->value
            );

        $this->assertEquals(
            300.00,
            (float) $response->json('data.total_amount')
        );

        $this->assertDatabaseHas('orders', [
            'notes' => 'My order',
        ]);

        $this->assertEquals(
            7,
            $product->fresh()->quantity_in_stock
        );
    }

    #[Test]
    public function it_creates_an_order_number_with_ord_prefix(): void
    {
        $response = $this->postJson(
            route('orders.store'),
            $this->validOrderPayload(),
            $this->auth()
        );

        $response->assertCreated();

        $this->assertStringStartsWith(
            'ORD-',
            $response->json('data.order_number')
        );
    }

    #[Test]
    public function it_creates_an_order_history_entry_on_creation(): void
    {
        $response = $this->postJson(
            route('orders.store'),
            $this->validOrderPayload(),
            $this->auth()
        );

        $response->assertCreated();

        $this->assertDatabaseHas('order_histories', [
            'order_id' => $response->json('data.id'),
            'action' => OrderHistoryActionEnum::Created->value,
        ]);
    }

    #[Test]
    public function it_rejects_an_order_when_stock_is_insufficient(): void
    {
        $product = $this->makeProduct(
            stock: 2
        );

        $this->postJson(
            route('orders.store'),
            [
                'currency_id' => $this->currency->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 99,
                    ],
                ],
            ],
            $this->auth()
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.items.0',
                "Product {$product->code} has only 2 items in stock."
            );
    }

    #[Test]
    public function it_rejects_an_order_for_a_non_existent_product(): void
    {
        $this->postJson(
            route('orders.store'),
            [
                'currency_id' => $this->currency->id,
                'items' => [
                    [
                        'product_id' => 99999,
                        'quantity' => 1,
                    ],
                ],
            ],
            $this->auth()
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.product_id',
            ]);
    }

    #[Test]
    #[DataProvider('storeValidationProvider')]
    public function it_validates_required_store_fields(
        array $payload,
        string $field
    ): void {
        $this->postJson(
            route('orders.store'),
            $payload,
            $this->auth()
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                $field,
            ]);
    }

    public static function storeValidationProvider(): array
    {
        return [
            'missing currency id' => [
                [
                    'items' => [
                        [
                            'product_id' => 1,
                            'quantity' => 1,
                        ],
                    ],
                ],
                'currency_id',
            ],
            'missing items' => [
                [
                    'currency_id' => 1,
                ],
                'items',
            ],
            'empty items array' => [
                [
                    'currency_id' => 1,
                    'items' => [],
                ],
                'items',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    #[Test]
    public function it_updates_order_notes_and_currency(): void
    {
        $order = $this->postJson(
            route('orders.store'),
            $this->validOrderPayload(),
            $this->auth()
        )
            ->assertCreated()
            ->json('data');

        $newCurrency = Currency::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->putJson(
            route('orders.update', $order['id']),
            [
                'notes' => 'Updated notes',
                'currency_id' => $newCurrency->id,
            ],
            $this->auth()
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.notes',
                'Updated notes'
            )
            ->assertJsonPath(
                'data.currency.id',
                $newCurrency->id
            );
    }

    #[Test]
    public function it_replaces_items_and_adjusts_stock_on_update(): void
    {
        $productA = $this->makeProduct(
            stock: 10,
            price: 50.00
        );

        $productB = $this->makeProduct(
            stock: 10,
            price: 80.00
        );

        $order = $this->postJson(
            route('orders.store'),
            [
                'currency_id' => $this->currency->id,
                'items' => [
                    [
                        'product_id' => $productA->id,
                        'quantity' => 4,
                    ],
                ],
            ],
            $this->auth()
        )
            ->assertCreated()
            ->json('data');

        $this->assertEquals(
            6,
            $productA->fresh()->quantity_in_stock
        );

        $this->putJson(
            route('orders.update', $order['id']),
            [
                'items' => [
                    [
                        'product_id' => $productB->id,
                        'quantity' => 3,
                    ],
                ],
            ],
            $this->auth()
        )->assertOk();

        $this->assertEquals(
            10,
            $productA->fresh()->quantity_in_stock
        );

        $this->assertEquals(
            7,
            $productB->fresh()->quantity_in_stock
        );
    }

    #[Test]
    public function it_records_history_on_update(): void
    {
        $order = $this->postJson(
            route('orders.store'),
            $this->validOrderPayload(),
            $this->auth()
        )
            ->assertCreated()
            ->json('data');

        $this->putJson(
            route('orders.update', $order['id']),
            [
                'notes' => 'Changed',
            ],
            $this->auth()
        )->assertOk();

        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order['id'],
            'action' => OrderHistoryActionEnum::Updated->value,
        ]);
    }

    #[Test]
    public function it_cannot_update_a_cancelled_order(): void
    {
        $order = $this->postJson(
            route('orders.store'),
            $this->validOrderPayload(),
            $this->auth()
        )
            ->assertCreated()
            ->json('data');

        $this->deleteJson(
            route('orders.destroy', $order['id']),
            [],
            $this->auth()
        )->assertOk();

        /*
         * The delete endpoint soft-deletes the order.
         * Normal route-model binding therefore returns 404 before
         * the update action or service is reached.
         */
        $this->putJson(
            route('orders.update', $order['id']),
            [
                'notes' => 'Should fail',
            ],
            $this->auth()
        )->assertNotFound();

        $this->assertSoftDeleted('orders', [
            'id' => $order['id'],
        ]);
    }

    #[Test]
    public function it_returns_404_updating_non_existent_order(): void
    {
        $this->putJson(
            route('orders.update', 99999),
            [
                'notes' => 'X',
            ],
            $this->auth()
        )->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    #[Test]
    public function it_cancels_and_soft_deletes_an_order(): void
    {
        $product = $this->makeProduct(
            stock: 10
        );

        $order = $this->postJson(
            route('orders.store'),
            [
                'currency_id' => $this->currency->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 5,
                    ],
                ],
            ],
            $this->auth()
        )
            ->assertCreated()
            ->json('data');

        $this->assertEquals(
            5,
            $product->fresh()->quantity_in_stock
        );

        $response = $this->deleteJson(
            route('orders.destroy', $order['id']),
            [],
            $this->auth()
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status.value',
                OrderStatusEnum::Cancelled->value
            );

        $this->assertEquals(
            10,
            $product->fresh()->quantity_in_stock
        );

        $this->assertSoftDeleted('orders', [
            'id' => $order['id'],
        ]);
    }

    #[Test]
    public function it_records_history_on_cancellation(): void
    {
        $order = $this->postJson(
            route('orders.store'),
            $this->validOrderPayload(),
            $this->auth()
        )
            ->assertCreated()
            ->json('data');

        $this->deleteJson(
            route('orders.destroy', $order['id']),
            [],
            $this->auth()
        )->assertOk();

        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order['id'],
            'action' => OrderHistoryActionEnum::Cancelled->value,
        ]);
    }

    #[Test]
    public function it_returns_404_deleting_non_existent_order(): void
    {
        $this->deleteJson(
            route('orders.destroy', 99999),
            [],
            $this->auth()
        )->assertNotFound();
    }

    #[Test]
    public function it_returns_401_without_token_on_store(): void
    {
        $this->postJson(
            route('orders.store'),
            $this->validOrderPayload()
        )->assertUnauthorized();
    }
}
