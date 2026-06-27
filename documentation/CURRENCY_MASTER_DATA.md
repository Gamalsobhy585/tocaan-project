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


## Business Rules

- A currency code must be unique.
- A currency code must contain exactly three alphabetic characters.
- Currency codes are stored in uppercase.
- Currency records are not deleted.
- Disabling a currency does not remove it from historical transactions.
- The administrative index returns active and inactive records.
- Transactional modules should prevent inactive currencies from being selected for new transactions.
