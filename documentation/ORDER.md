# Order Module

## Overview

The Order module manages order creation, updates, cancellation, stock reservation, order history, audit users, pagination, status filtering, and soft deletion.

The module provides these operations:

- Create an order.
- Update an order.
- Cancel and soft-delete an order.
- List orders using pagination.
- Filter orders by status.
- Search orders by order number.

## Order Status

Order statuses are stored as integers.

| Value | Status | Description |
|---:|---|---|
| `0` | Pending | The order was created but is not confirmed yet. |
| `1` | Confirmed | The order was reviewed and confirmed. |
| `2` | Cancelled | The order was cancelled and soft-deleted. |

Example enum:

```php
<?php

namespace App\Enums;

enum OrderStatusEnum: int
{
    /**
     * Order was created but is not confirmed yet.
     */
    case Pending = 0;

    /**
     * Order was reviewed and confirmed.
     */
    case Confirmed = 1;

    /**
     * Order was cancelled and soft-deleted.
     */
    case Cancelled = 2;
}
```

## Order History Actions

Order history actions are stored as integers.

| Value | Action | Description |
|---:|---|---|
| `0` | Created | The order was created. |
| `1` | Updated | The order was updated. |
| `2` | Cancelled | The order was cancelled and soft-deleted. |

## Database Tables

### `orders`

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT | Primary key |
| `order_number` | VARCHAR(40) | Unique generated order number |
| `currency_id` | BIGINT | Foreign key to `currencies` |
| `status` | TINYINT UNSIGNED | `0 = pending`, `1 = confirmed`, `2 = cancelled` |
| `total_amount` | DECIMAL(15,2) | Calculated order total |
| `notes` | TEXT, nullable | Optional order notes |
| `created_by` | BIGINT, nullable | User who created the order |
| `updated_by` | BIGINT, nullable | User who last updated the order |
| `cancelled_by` | BIGINT, nullable | User who cancelled the order |
| `cancelled_at` | TIMESTAMP, nullable | Cancellation date and time |
| `created_at` | TIMESTAMP | Laravel creation timestamp |
| `updated_at` | TIMESTAMP | Laravel update timestamp |
| `deleted_at` | TIMESTAMP, nullable | Soft-delete timestamp |

### `order_items`

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT | Primary key |
| `order_id` | BIGINT | Foreign key to `orders` |
| `product_id` | BIGINT, nullable | Foreign key to `products` |

| `quantity` | UNSIGNED INTEGER | Ordered quantity |
| `unit_price` | DECIMAL(15,2) | Product price when the order was created or updated |
| `line_total` | DECIMAL(15,2) | `quantity × unit_price` |
| `created_at` | TIMESTAMP | Laravel creation timestamp |
| `updated_at` | TIMESTAMP | Laravel update timestamp |

Product snapshots are stored so that historical orders remain correct when a product name, code, or price changes later.

### `order_histories`

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT | Primary key |
| `order_id` | BIGINT | Foreign key to `orders` |
| `action` | TINYINT UNSIGNED | `0 = created`, `1 = updated`, `2 = cancelled` |
| `old_status` | TINYINT UNSIGNED, nullable | Status before the action |
| `new_status` | TINYINT UNSIGNED, nullable | Status after the action |
| `changes` | JSON, nullable | Before-and-after order data |
| `performed_by` | BIGINT, nullable | User who performed the action |
| `performed_by_name` | VARCHAR, nullable | User-name snapshot |
| `created_at` | TIMESTAMP | History creation timestamp |
| `updated_at` | TIMESTAMP | Laravel update timestamp |

Order-history records should not use soft deletion because they are audit records.

## API Endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/orders` | Return paginated orders |
| `POST` | `/api/orders` | Create an order |
| `PUT` | `/api/orders/{order}` | Update an order |
| `DELETE` | `/api/orders/{order}` | Cancel and soft-delete an order |

The routes should be protected by the configured JWT authentication middleware.

## List and Filter Orders

Example:

```text
GET /api/orders?page=1&per_page=15
```

Filter pending orders:

```text
GET /api/orders?status=0
```

Filter confirmed orders:

```text
GET /api/orders?status=1
```

Filter cancelled orders:

```text
GET /api/orders?status=2
```

Search by order number:

```text
GET /api/orders?search=ORD-
```

Pagination rules:

- Default page size: `15`.
- Maximum page size: `100`.
- Filtering by `status=2` automatically includes soft-deleted orders.

## Create Order

Example request:

```json
{
  "currency_id": 1,
  "notes": "Deliver as soon as possible",
  "items": [
    {
      "product_id": 3,
      "quantity": 2
    },
    {
      "product_id": 7,
      "quantity": 1
    }
  ]
}
```

The frontend does not send `unit_price`, `line_total`, or `total_amount`.

The backend performs these steps:

1. Validate that the currency is active.
2. Validate that every product exists.
3. Lock the selected product rows.
4. Check available stock.
5. Read unit prices from the Product table.
6. Decrease product stock.
7. Calculate every line total.
8. Calculate the order total.
9. Create the order and its items.
10. Record `created_by`.
11. Create an order-history record with action `Created`.

New orders start with:

```text
status = 0
```

## Update Order

Example request:

```json
{
  "status": 1,
  "notes": "Order reviewed",
  "items": [
    {
      "product_id": 3,
      "quantity": 3
    }
  ]
}
```

The update endpoint can change:

- Currency.
- Notes.
- Status between pending and confirmed.
- Order items.

Cancellation status cannot be sent through the update endpoint.

This is not accepted:

```json
{
  "status": 2
}
```

Cancellation must use:

```text
DELETE /api/orders/{order}
```

When order items change, the backend:

1. Locks all affected products.
2. Restores quantities from the old order items.
3. Validates the new requested quantities.
4. Reserves the new quantities.
5. Replaces the old order items.
6. Recalculates `total_amount`.
7. Records `updated_by`.
8. Creates an order-history record containing before-and-after snapshots.

## Cancel and Delete Order

Example:

```text
DELETE /api/orders/1
```

Deleting an order means cancellation plus soft deletion.

The backend performs these steps:

1. Lock the order.
2. Lock the related products.
3. Restore the ordered quantities to product stock.
4. Set `status = 2`.
5. Record `cancelled_by`.
6. Record `cancelled_at`.
7. Record `updated_by`.
8. Create an order-history record with action `Cancelled`.
9. Soft-delete the order.

The database record remains available through `withTrashed()`.

## Stock Rules

- Product stock cannot become negative.
- Orders cannot reserve more than the available quantity.
- Creating an order decreases stock.
- Updating order items restores old stock before reserving new quantities.
- Cancelling an order restores its quantities.
- Stock operations must run inside a database transaction.
- Product rows should use `lockForUpdate()` during stock changes.

## Audit Rules

The `orders` table records:

- `created_by`
- `updated_by`
- `cancelled_by`
- `cancelled_at`

The `order_histories` table records every important action.

Each history record stores:

- Action type.
- Old status.
- New status.
- Before-and-after data.
- User ID.
- User-name snapshot.
- Action timestamp.

## Soft Deletion

The `Order` model uses:

```php
use Illuminate\Database\Eloquent\SoftDeletes;
```

Normal queries exclude cancelled orders after deletion.

Use:

```php
Order::withTrashed();
```

to include active and deleted orders.

Use:

```php
Order::onlyTrashed();
```

to return deleted orders only.

## Generated Files

```text
app/
├── Enums/
│   ├── OrderStatusEnum.php
│   └── OrderHistoryActionEnum.php
├── Models/
│   ├── Order.php
│   ├── OrderItem.php
│   └── OrderHistory.php
└── Modules/
    └── Order/
        ├── Controllers/
        │   └── OrderController.php
        ├── Repositories/
        │   ├── Interfaces/
        │   │   └── IOrderRepository.php
        │   └── Implementation/
        │       └── OrderRepository.php
        ├── Requests/
        │   ├── IndexOrderRequest.php
        │   ├── StoreOrderRequest.php
        │   └── UpdateOrderRequest.php
        ├── Resources/
        │   ├── OrderResource.php
        │   ├── OrderItemResource.php
        │   └── OrderHistoryResource.php
        ├── Routes/
        │   └── api.php
        └── Services/
            ├── Interfaces/
            │   └── IOrderService.php
            └── OrderService.php

database/
├── migrations/
│   ├── create_orders_table.php
│   ├── create_order_items_table.php
│   └── create_order_histories_table.php
└── seeders/
    └── OrderSeeder.php
```

## Generation Commands

```bash
php artisan generate:module Order

php artisan make:model OrderItem
php artisan make:model OrderHistory

php artisan make:migration create_orders_table
php artisan make:migration create_order_items_table
php artisan make:migration create_order_histories_table

php artisan make:seeder OrderSeeder

php artisan make:class Enums/OrderStatusEnum
php artisan make:class Enums/OrderHistoryActionEnum
```

Create these files manually:

```text
app/Modules/Order/Requests/IndexOrderRequest.php
app/Modules/Order/Resources/OrderItemResource.php
app/Modules/Order/Resources/OrderHistoryResource.php
```

## Service Container Bindings

Register these bindings in `AppServiceProvider`:

```php
$this->app->bind(
    \App\Modules\Order\Repositories\Interfaces\IOrderRepository::class,
    \App\Modules\Order\Repositories\Implementation\OrderRepository::class
);

$this->app->bind(
    \App\Modules\Order\Services\Interfaces\IOrderService::class,
    \App\Modules\Order\Services\OrderService::class
);
```

## Seeder Behavior

The Order seeder should create orders through `IOrderService`.

It should not create order items directly because direct insertion would bypass:

- Stock checks.
- Stock deduction.
- Total calculation.
- Order-history creation.
- User audit fields.

Run:

```bash
php artisan db:seed --class=OrderSeeder
```

## Business Rules

- Order numbers are unique.
- New orders start as pending.
- Prices are read from the Product table.
- Totals are calculated by the backend.
- An inactive currency cannot be used for a new order.
- Product IDs cannot be duplicated inside the same request.
- An order cannot reserve unavailable stock.
- Cancelled orders cannot be updated.
- Cancellation restores stock.
- Cancellation sets status to `2`.
- Cancellation records the authenticated user.
- Orders use soft deletion.
- Order histories are permanent audit records.
- All create, update, and cancellation operations use database transactions.
