---
layout: default
title: Testing Guide
---

# Testing Guide

## Run the Test Suite

```bash
php artisan test
```

To stop at the first failure:

```bash
php artisan test --stop-on-failure
```

To run a specific test file:

```bash
php artisan test tests/Feature/PaymentTest.php
```

## Recommended Feature Tests

### Authentication

- User can register
- User can log in
- Invalid credentials are rejected
- Protected endpoints reject missing tokens
- Authenticated user details are returned

### Products

- Product can be created
- Product can be updated
- Product can be deleted
- Product list is returned
- Invalid price or stock values are rejected

### Currencies

- Currency can be created
- Duplicate currency codes are rejected
- Currency can be updated and deleted

### Orders

- Order can be created with items
- Order total is calculated correctly
- Product data is returned with order items
- Order can be updated
- Order can be deleted according to business rules
- Orders can be filtered by status
- Invalid products are rejected
- Invalid quantities are rejected

### Payments

- Payment is linked to an existing order
- Unsupported payment method is rejected
- Correct gateway strategy is selected
- Successful result updates payment status
- Failed result stores the failure reason
- Payments can be listed
- Payments can be filtered by order
- Duplicate or invalid payment attempts follow the defined business rules

## Unit Tests for Gateway Strategies

Each strategy should be tested independently from HTTP and database concerns.

Example cases:

- Strategy returns the expected key
- Strategy returns a successful result for valid simulated input
- Strategy returns a failed result for configured failure input
- Result contains a transaction reference on success
- Result contains a failure reason on failure

## Manual Postman Test

1. Start the API.
2. Open the Postman workspace.
3. Set `base_url`.
4. Run registration or login.
5. Store the returned token.
6. Create required master data or run seeders.
7. Create an order.
8. Process a payment using each available method.
9. Confirm the stored payment status and gateway response.
10. Retrieve payments for the order.

## Database Reset for Testing

```bash
php artisan migrate:fresh --seed
```

> This deletes all current data in the configured database.
