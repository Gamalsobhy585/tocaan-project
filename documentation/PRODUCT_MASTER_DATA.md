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


## Business Rules

- Product codes are unique.
- Product codes are stored in uppercase.
- Stock quantity cannot be negative.
- Unit price cannot be negative.
- Single and bulk delete operations permanently remove the selected records.
- Excel import identifies products by `code`.
- Existing products are updated during import instead of duplicated.
