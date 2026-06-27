# Currency Master Data

## Overview

The Currency module is a master-data module used to manage the currencies supported by the application.

The initial records are:

| Code | English name | Arabic name |
|---|---|---|
| USD | US Dollar | الدولار الأمريكي |
| EGP | Egyptian Pound | الجنيه المصري |
| EUR | Euro | اليورو |
| GBP | British Pound | الجنيه الإسترليني |

> The requirement says three records, but four currency codes are listed. This module seeds all four listed currencies.

## Database Table

Table name: `currencies`

| Column | Type | Rules |
|---|---|---|
| `id` | BIGINT | Primary key |
| `name_ar` | VARCHAR(100) | Required |
| `name_en` | VARCHAR(100) | Required |
| `code` | CHAR(3) | Required, uppercase, unique |
| `is_active` | BOOLEAN | Defaults to `true` |
| `created_at` | TIMESTAMP | Managed by Laravel |
| `updated_at` | TIMESTAMP | Managed by Laravel |

Currencies are not deleted. They are enabled or disabled through `is_active`.

## Supported Operations

The module exposes only three operations:

1. List currencies.
2. Add a currency.
3. Toggle a currency between active and inactive.

No update, show, or delete endpoints are exposed.

## API Endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/currencies` | Return all currencies |
| `POST` | `/api/currencies` | Add a currency |
| `PATCH` | `/api/currencies/{currency}/toggle-active` | Toggle `is_active` |

The routes are expected to be protected by the configured JWT middleware.

## Add Currency Request

Example:

```json
{
  "name_ar": "الريال السعودي",
  "name_en": "Saudi Riyal",
  "code": "SAR",
  "is_active": true
}
```

Validation rules:

- `name_ar`: required string, maximum 100 characters.
- `name_en`: required string, maximum 100 characters.
- `code`: required alphabetic code, exactly 3 characters, unique.
- `is_active`: optional boolean.
- Currency codes are normalized to uppercase before validation.

## Redis Cache

The currency list is cached in Redis under:

```text
master_data:currencies:all
```

The list is cached permanently because currency master data changes rarely.

The cache is cleared after:

- Adding a currency.
- Toggling a currency status.

The next index request rebuilds the cache from the database.

Recommended environment configuration:

```env
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Initial Data

Run:

```bash
php artisan db:seed --class=CurrencySeeder
```

Or call the seeder from `DatabaseSeeder` and run:

```bash
php artisan db:seed
```

The seeder uses `updateOrCreate`, so it can be run safely more than once.

## Generation Commands

```bash
php artisan generate:module Currency
php artisan make:migration create_currencies_table
php artisan make:seeder CurrencySeeder
```

The custom module generator already creates the model, controller, request, resource, repository, service, and route files. Do not run `make:model Currency` after running `generate:module Currency`.

## Dependency Bindings

The following interfaces must be bound to their implementations in `AppServiceProvider`:

- `ICurrencyRepository` → `CurrencyRepository`
- `ICurrencyService` → `CurrencyService`

Without these bindings, Laravel cannot inject the service and repository interfaces.

## Business Rules

- A currency code must be unique.
- A currency code must contain exactly three alphabetic characters.
- Currency codes are stored in uppercase.
- Currency records are not deleted.
- Disabling a currency does not remove it from historical transactions.
- The administrative index returns active and inactive records.
- Transactional modules should prevent inactive currencies from being selected for new transactions.
