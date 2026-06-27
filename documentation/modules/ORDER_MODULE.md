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
