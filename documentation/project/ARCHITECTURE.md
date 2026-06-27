---
layout: default
title: Architecture
---

# Architecture

## Architecture Goal

The project is designed for clear separation of responsibilities, low coupling, and easy extension. Business modules are isolated under `app/Modules`.

## Module Structure

A typical module follows this structure:

```text
app/Modules/{ModuleName}/
├── Controllers/
├── DTOs/
├── Enums/
├── Exceptions/
├── Listeners/
├── Providers/
├── Repositories/
│   ├── Interfaces/
│   └── Implementation/
├── Requests/
├── Resources/
├── Routes/
└── Services/
    ├── Interfaces/
    └── Implementation/
```

Not every module needs every directory. A directory should exist only when the module has that responsibility.

## Request Flow

```text
Client
  ↓
API Route
  ↓
Controller
  ↓
Form Request
  ↓
Service Interface
  ↓
Service Implementation
  ↓
Repository Interface
  ↓
Repository Implementation
  ↓
Eloquent Model
  ↓
Database
```

The response is transformed through an API Resource before it is returned to the client.

## Layer Responsibilities

### Controller

- Receives HTTP requests
- Calls the correct service method
- Returns resources and status codes
- Does not contain complex business logic

### Form Request

- Validates incoming data
- Applies request-level authorization when needed
- Keeps validation rules out of controllers

### Service

- Contains application and business rules
- Coordinates repositories and related modules
- Controls database transactions
- Selects payment gateway strategies

### Repository

- Contains database-query and persistence logic
- Hides Eloquent details from the service layer
- Provides a replaceable data-access contract

### API Resource

- Controls the JSON response shape
- Loads related information consistently
- Prevents models from being returned directly

### Enum

- Represents fixed statuses and types
- Uses numeric backed values for database storage
- Keeps status meaning explicit in application code

## Payment Strategy Pattern

The Payment module uses a Strategy pattern.

```text
PaymentController
    ↓
PaymentService
    ↓
PaymentGatewayResolver
    ↓
PaymentGatewayStrategy interface
    ├── CreditCardGateway
    ├── PayPalGateway
    └── FutureGateway
```

Every gateway strategy implements the same contract:

```php
interface PaymentGatewayStrategy
{
    public function key(): string;

    public function process(
        PaymentGatewayContext $context
    ): PaymentGatewayResult;
}
```

The resolver selects the strategy whose key matches the payment method's `strategy_key`.

## Adding a New Payment Gateway

1. Create a class that implements `PaymentGatewayStrategy`.
2. Return a unique value from `key()`.
3. Implement the `process()` method.
4. Register the strategy in the Payment module provider or configured strategy collection.
5. Add or seed a payment method whose `strategy_key` matches the strategy key.

Existing controllers, repositories, and payment-processing rules should not require modification.

## Dependency Injection

Interfaces are bound to implementations in module service providers.

Example:

```php
$this->app->bind(
    IPaymentService::class,
    PaymentService::class,
);
```

The controller depends on the interface, not the concrete implementation.

## Transaction Boundaries

Operations that change multiple records should be wrapped in a database transaction. Examples include:

- Creating an order with its order items
- Updating an order and replacing its items
- Creating and processing a payment
- Updating both payment and order states after a successful payment

## Modularity Rules

- A module owns its business rules.
- Cross-module access should happen through clear service contracts.
- Controllers should remain thin.
- Repositories should not contain HTTP logic.
- Services should not construct HTTP responses.
- Gateway classes should not query unrelated data directly.
- Enums should replace unexplained numeric literals.
