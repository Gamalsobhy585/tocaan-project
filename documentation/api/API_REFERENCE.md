---
layout: default
title: API Reference
---

# API Reference

## Postman Workspace

The maintained API requests are available here:

[Open Tocaan Postman Workspace](https://martian-shadow-736975.postman.co/workspace/1fe9452d-f7b9-4ca3-a0a5-8e15ccfa4db8)

## Base URLs

Local development:

```text
http://127.0.0.1:8000/api
```

Postman environment variable:

```text
{{base_url}}
```

## Authentication

Protected endpoints expect a JWT Bearer token:

```http
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: application/json
```

The token should be taken from the login response and stored in the Postman `token` variable.

## API Groups

The workspace should be organized into these groups:

1. Authentication
2. Products
3. Currencies
4. Orders
5. Payment Methods
6. Payments

## Recommended Postman Variables

| Variable | Example | Purpose |
|---|---|---|
| `base_url` | `http://127.0.0.1:8000/api` | API root URL |
| `token` | JWT token | Authentication |
| `product_id` | `1` | Product requests |
| `currency_id` | `1` | Currency requests |
| `order_id` | `1` | Order requests |
| `payment_method_id` | `1` | Payment processing |
| `payment_id` | `1` | Payment details |

## Recommended Request Flow

```text
Register or Login
    ↓
Store JWT Token
    ↓
List Products and Currencies
    ↓
Create Order
    ↓
View Order
    ↓
List Payment Methods
    ↓
Process Payment
    ↓
View Payments for the Order
```

## Expected Response Principles

- Validation errors return field-level error details.
- Authentication failures return an unauthorized response.
- Missing resources return a not-found response.
- Successful creation returns the created resource.
- Lists use API resources and pagination where configured.
- Enum values are stored as numbers and may be exposed with readable labels by resources.

## Keeping Postman Updated

Whenever an endpoint changes:

1. Update the request method and URL.
2. Update request examples.
3. Update successful-response examples.
4. Update validation-error examples.
5. Confirm authentication settings.
6. Confirm environment variables.
7. Update the matching module Markdown file.
