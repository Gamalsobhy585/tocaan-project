# Payment Methods and Payments Module

## 1. Overview

This module is split into two parts:

1. **Payment Method master data**
   - Stores the methods available to users.
   - Examples: Credit Card and PayPal.
   - Each record points to a registered payment gateway strategy.

2. **Payment processing**
   - Creates a pending payment for an order.
   - Resolves the correct gateway strategy.
   - Simulates the gateway response.
   - Changes the payment to successful or failed.
   - Stores gateway responses .
   - Supports paginated payment lists and order-specific payment lists.

The design uses the **Strategy Pattern**.

Adding a gateway requires only:

1. Creating a new strategy class.
2. Registering that class in `PaymentServiceProvider`.
3. Adding the payment-method record to the database or seeder.

The payment service and controller do not need to change.

---

## 2. Business Rules

- Payment statuses use integer-backed enums.
- Payment methods are database master data, not enum values.
- A payment starts with status `0 = pending`.
- A gateway changes it to `1 = successful` or `2 = failed`.
- A cancelled or soft-deleted order cannot receive a new payment.
- Payment amount cannot exceed the remaining unpaid amount.
- Successful and currently pending amounts are considered when calculating the remaining amount.
- The backend reads the order currency and does not accept currency from the client.
- Payments are not deleted.
- Gateway credentials and secrets must remain in `.env` or secure configuration, not in the database.
- Sensitive card details must never be saved in `gateway_payload` or `gateway_response`.
- A successful payment does not automatically change the order status. Order workflow and payment workflow remain separate.

---

## 3. Payment Status Enum

| Value | Status | Meaning |
|---:|---|---|
| `0` | Pending | Payment record was created and is waiting for a gateway result |
| `1` | Successful | Gateway simulation succeeded |
| `2` | Failed | Gateway simulation failed |

---


## 5. API Endpoints

### Payment methods

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/payment-methods` | List payment methods |
| `POST` | `/api/payment-methods` | Add payment-method metadata for a registered strategy |
| `PATCH` | `/api/payment-methods/{paymentMethod}/toggle-active` | Activate or deactivate a method |

### Payments

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/api/orders/{order}/payments/process` | Process a simulated payment |
| `GET` | `/api/payments` | View all payments using filters and pagination |
| `GET` | `/api/orders/{order}/payments` | View payments for one order |
| `GET` | `/api/payments/{payment}` | View one payment  |

---


## Suggested Structure

```text
app/
├── Enums/
│   ├── PaymentStatusEnum.php
│   
│
├── Models/
│   ├── PaymentMethod.php
│   ├── Payment.php
│  
│
└── Modules/
    ├── PaymentMethod/
    │   ├── Controllers/
    │   │   └── PaymentMethodController.php
    │   ├── Repositories/
    │   │   ├── Interfaces/
    │   │   │   └── IPaymentMethodRepository.php
    │   │   └── Implementation/
    │   │       └── PaymentMethodRepository.php
    │   ├── Requests/
    │   │   └── StorePaymentMethodRequest.php
    │   ├── Resources/
    │   │   └── PaymentMethodResource.php
    │   ├── Routes/
    │   │   └── api.php
    │   └── Services/
    │       ├── Interfaces/
    │       │   └── IPaymentMethodService.php
    │       └── PaymentMethodService.php
    │
    └── Payment/
        ├── Controllers/
        │   └── PaymentController.php
        ├── DTOs/
        │   ├── PaymentGatewayContext.php
        │   └── PaymentGatewayResult.php
        ├── Exceptions/
        │   └── UnsupportedPaymentGatewayException.php
        ├── Gateways/
        │   ├── Contracts/
        │   │   └── PaymentGatewayStrategy.php
        │   ├── Strategies/
        │   │   ├── CreditCardGatewayStrategy.php
        │   │   └── PayPalGatewayStrategy.php
        │   └── PaymentGatewayRegistry.php
        ├── Providers/
        │   └── PaymentServiceProvider.php
        ├── Repositories/
        │   ├── Interfaces/
        │   │   └── IPaymentRepository.php
        │   └── Implementation/
        │       └── PaymentRepository.php
        ├── Requests/
        │   ├── IndexPaymentRequest.php
        │   └── ProcessPaymentRequest.php
        ├── Resources/
        │   ├── PaymentResource.php
        │ 
        ├── Routes/
        │   └── api.php
        └── Services/
            ├── Interfaces/
            │   └── IPaymentService.php
            └── PaymentService.php

database/
├── migrations/
│   ├── create_payment_methods_table.php
│   ├── create_payments_table.php
│   
└── seeders/
    ├── PaymentMethodSeeder.php
    └── PaymentSeeder.php
```

---


# Important Production Notes

##  Do Not Store Sensitive Payment Data

Do not store:

- Full credit-card number.
- CVV.
- PayPal passwords.
- Gateway secret keys.
- Raw authentication tokens.

Store only safe provider references and sanitized response data.

##  Webhooks

Real payment gateways often return the final result asynchronously.

For a production gateway, add:

```text
POST /api/payment-webhooks/{gateway}
```

The webhook handler should:

1. Verify the gateway signature.
2. Find the payment by provider reference.
3. Lock the payment.
4. Ignore already finalized payments.
5. Update status.
6. Return a successful HTTP response.

## Idempotency

Use an `idempotency_key` to prevent the same client action from creating duplicate payment attempts.

The database unique constraint protects against concurrent duplicate requests.

##  Financial Records

Do not soft-delete or permanently delete payments .

Create refund and void workflows as separate operations rather than changing or deleting old financial records.
