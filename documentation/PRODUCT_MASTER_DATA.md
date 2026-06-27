# Product Master Data

## Overview

The Product module manages products used by the order system.

Each product contains:

| Field | Description |
|---|---|
| `name_ar` | Arabic product name |
| `name_en` | English product name |
| `code` | Unique product code |
| `quantity_in_stock` | Current available stock quantity |
| `unit_price` | Product price per unit |

The current task uses permanent deletion for single and bulk delete operations. If products will later be referenced directly by order records, replace permanent deletion with soft deletion or an `is_active` flag.

## Database Table

Table name: `products`

| Column | Type | Rules |
|---|---|---|
| `id` | BIGINT | Primary key |
| `name_ar` | VARCHAR(150) | Required |
| `name_en` | VARCHAR(150) | Required |
| `code` | VARCHAR(50) | Required, unique |
| `quantity_in_stock` | UNSIGNED INTEGER | Required, defaults to 0 |
| `unit_price` | DECIMAL(15,2) | Required |
| `created_at` | TIMESTAMP | Managed by Laravel |
| `updated_at` | TIMESTAMP | Managed by Laravel |

## API Operations

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/products` | Return paginated products |
| `POST` | `/api/products` | Add one product |
| `POST` | `/api/products/import-bulk` | Import products from Excel or CSV |
| `DELETE` | `/api/products/delete-bulk` | Permanently delete multiple products |
| `DELETE` | `/api/products/{product}` | Permanently delete one product |

## Pagination

Example:

```text
GET /api/products?page=1&per_page=15&search=laptop
```

Rules:

- Default `per_page` is 15.
- Maximum `per_page` is 100.
- Search checks `name_ar`, `name_en`, and `code`.

## Add Product

Example request:

```json
{
  "name_ar": "حاسوب محمول",
  "name_en": "Laptop",
  "code": "prd-001",
  "quantity_in_stock": 25,
  "unit_price": 25000.00
}
```

The code is normalized to uppercase before validation and storage.

## Bulk Excel Import

Supported uploaded file extensions:

- `.xlsx`
- `.xls`
- `.csv`

The first row must contain these exact headings:

```text
name_ar,name_en,code,quantity_in_stock,unit_price
```

Example:

| name_ar | name_en | code | quantity_in_stock | unit_price |
|---|---|---|---:|---:|
| حاسوب محمول | Laptop | PRD-001 | 25 | 25000 |
| فأرة | Mouse | PRD-002 | 100 | 350 |

Import behavior:

- New codes are inserted.
- Existing codes are updated.
- Codes are normalized to uppercase.
- Invalid rows are skipped and returned in the API response.
- Imports use batch inserts and chunk reading to reduce memory usage.

## Bulk Delete

Example request:

```json
{
  "ids": [1, 2, 3]
}
```

The selected products are permanently deleted.

## Factory and Seeder

The factory generates sample product names, unique codes, stock quantities, and prices.

Run:

```bash
php artisan db:seed --class=ProductSeeder
```

The default seeder creates 50 product records.

## Generation Commands

```bash
composer require maatwebsite/excel

php artisan generate:module Product
php artisan make:migration create_products_table
php artisan make:factory ProductFactory --model=Product
php artisan make:seeder ProductSeeder
php artisan make:import ProductsImport --model=Product
```

The custom module generator creates generic CRUD files. Replace its generated Product controller, repository, service, requests, resource, model, and routes with the Product-specific implementations.

## Service Container Bindings

Register these bindings in `AppServiceProvider`:

- `IProductRepository` to `ProductRepository`
- `IProductService` to `ProductService`

## Business Rules

- Product codes are unique.
- Product codes are stored in uppercase.
- Stock quantity cannot be negative.
- Unit price cannot be negative.
- Single and bulk delete operations permanently remove the selected records.
- Excel import identifies products by `code`.
- Existing products are updated during import instead of duplicated.
