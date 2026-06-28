# Tocaan Project API

A modular REST API built with **Laravel 13** as a technical assessment for **Tocaan Company**.

The project provides authentication, product and currency master data, order management, payment-method management, and payment processing using an extensible gateway architecture.

## Project Links

* **GitHub Repository:** [Gamalsobhy585/tocaan-project](https://github.com/Gamalsobhy585/tocaan-project)
* **Postman Workspace:** [Tocaan API Workspace](https://martian-shadow-736975.postman.co/workspace/1fe9452d-f7b9-4ca3-a0a5-8e15ccfa4db8)

---

## Project Scope

The project implements a backend API for managing:

* User authentication using JWT
* Product master data
* Currency master data
* Orders
* Order items
* Payment methods
* Payments
* Payment-gateway processing
* Standardized API responses
* Request validation
* Database seeders
* Modular business logic

The application is API-based and does not include a frontend user interface.

---

## Requirements

Before installing the project, make sure the following tools are available:

* PHP compatible with the version required in `composer.json`
* Composer
* MySQL
* Git
* A local web server or Laravel development server

Optional tools:

* Postman
* Docker

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/Gamalsobhy585/tocaan-project.git
```

Move into the project directory:

```bash
cd tocaan-project
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create the environment file

For Linux or macOS:

```bash
cp .env.example .env
```

For Windows Command Prompt:

```cmd
copy .env.example .env
```

For Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

### 4. Generate the application key

```bash
php artisan key:generate
```

### 5. Configure the database

Update the database configuration in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tocaan_project
DB_USERNAME=root
DB_PASSWORD=
```

Create the configured database before running the migrations.

### 6. Generate the JWT secret

```bash
php artisan jwt:secret
```

This command adds the JWT secret to the `.env` file.

### 7. Run migrations and seeders

```bash
php artisan migrate --seed
```

To rebuild the database completely:

```bash
php artisan migrate:fresh --seed
```

### 8. Clear cached configuration

```bash
php artisan optimize:clear
```

### 9. Start the application

```bash
php artisan serve
```

The local API will normally be available at:

```text
http://127.0.0.1:8000
```

---

## Environment Configuration

Important environment variables include:

```env
APP_NAME="Tocaan Project"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tocaan_project
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=
```

Do not commit the `.env` file or expose database passwords and JWT secrets.

---

## Main Features

### Authentication

The Authentication module provides JWT-based authentication.

Main operations include:

* Register
* Login
* Logout
* Retrieve authenticated user information

Protected endpoints require a valid JWT token.

Example authorization header:

```http
Authorization: Bearer YOUR_ACCESS_TOKEN
```

---

### Currency Master Data

The Currency module manages the currencies used by products, orders, and payments.

Main operations include:

* Create a currency
* View currencies
* Toggle currency field (is_active)

---

### Product Master Data

The Product module manages the products available for ordering.

A product may contain information such as:

* Arabic name
* English name
* Product code
* Available stock quantity
* Unit price

Main operations include:

* Create a product
* View products
* Filter and paginate products
* Delete a product
* Import product data
* Seed initial product records

---

### Order Management

The Order module handles order creation and order-item management.

Main operations include:

* Create an order
* View all orders
* Filter orders by status
* View a specific order
* Update an order
* Delete an order

An order contains one or more order items.

Each order item stores information such as:

* Product
* Quantity
* Unit price
* Line total

Product information can be retrieved through the product relation.

---

### Payment Methods

The Payment Method module manages the payment methods supported by the application.

Examples include:

* Credit card
* PayPal
* Cash
* Other configured gateways

A payment method can be connected to a gateway strategy using a strategy key.

This allows the application to select the correct payment-processing logic dynamically.

---

### Payment Processing

The Payment module handles payment operations for orders.

Main operations include:

* Process a payment for an order
* View all payments
* View payments associated with a specific order
* Track payment status
* Store gateway responses
* Store transaction references
* Store failure reasons

Supported payment statuses include:

* Pending
* Successful
* Failed

Payment statuses are stored using numeric enums instead of string values.

---

## Architecture

The project follows a modular architecture.

Each business area is separated into its own module under:

```text
app/Modules/
```

The main modules are:

```text
app/Modules/
├── Authentication/
├── Currency/
├── Order/
├── Payment/
├── PaymentMethod/
└── Product/
```

A module may contain:

```text
ModuleName/
├── Controllers/
├── DTOs/
├── Exceptions/
├── Gateways/
├── Imports/
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

This structure keeps controllers, business logic, database access, validation, resources, and module routes separated.

---

## Design Patterns

### Service Pattern

Services contain application business logic.

Controllers communicate with service interfaces instead of directly implementing business operations.

Example:

```php
public function __construct(
    private readonly IOrderService $service
) {
}
```

Benefits include:

* Smaller controllers
* Reusable business logic
* Easier unit testing
* Clear separation of responsibilities

---

### Repository Pattern

Repositories are responsible for database-access logic.

Services communicate with repository interfaces rather than directly depending on Eloquent queries.

Example structure:

```text
Repositories/
├── Interfaces/
│   └── IProductRepository.php
└── Implementation/
    └── ProductRepository.php
```

Benefits include:

* Centralized database queries
* Reduced duplication
* Easier testing
* Lower coupling between business logic and data access

---

### Strategy Pattern

Payment gateways use the Strategy pattern.

Each payment gateway implements a shared contract:

```php
PaymentGatewayStrategy
```

The contract defines the gateway key and payment-processing operation.

Example:

```php
interface PaymentGatewayStrategy
{
    public function key(): string;

    public function process(
        PaymentGatewayContext $context
    ): PaymentGatewayResult;
}
```

The payment service selects the required gateway based on the payment method's strategy key.

This design allows new gateways to be added without modifying the main payment-processing logic.

---

## Adding a New Payment Gateway

To add a new payment gateway:

1. Create a gateway class inside:

```text
app/Modules/Payment/Gateways/
```

2. Implement:

```php
PaymentGatewayStrategy
```

3. Define a unique gateway key:

```php
public function key(): string
{
    return 'new_gateway';
}
```

4. Implement the gateway processing logic:

```php
public function process(
    PaymentGatewayContext $context
): PaymentGatewayResult {
    // Gateway processing logic
}
```

5. Return either a successful or failed gateway result.

Example successful result:

```php
return PaymentGatewayResult::success(
    transactionReference: 'TRANSACTION_REFERENCE',
    response: [
        'message' => 'Payment processed successfully',
    ]
);
```

6. Register the strategy in the payment service provider or gateway registry.

7. Add or update the related payment-method record with the same strategy key.

This process keeps gateway-specific logic isolated from the payment service.

---

## Request Flow

A typical API request follows this flow:

```text
HTTP Request
    ↓
Route
    ↓
Form Request Validation
    ↓
Controller
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
    ↓
API Resource
    ↓
JSON Response
```

For payment processing, the flow includes the gateway strategy:

```text
Payment Controller
    ↓
Payment Service
    ↓
Payment Repository
    ↓
Payment Gateway Resolver
    ↓
Selected Gateway Strategy
    ↓
Gateway Result
    ↓
Payment Status Update
    ↓
JSON Response
```

---

## Technology Stack

* PHP
* Laravel 13
* MySQL
* JWT Authentication
* Eloquent ORM
* Laravel Form Requests
* Laravel API Resources
* Laravel Service Container
* Postman

---

## API Usage

The project exposes REST API endpoints through Laravel route files.

The complete endpoint collection is available in the Postman workspace:

[Open the Tocaan Postman Workspace](https://martian-shadow-736975.postman.co/workspace/1fe9452d-f7b9-4ca3-a0a5-8e15ccfa4db8)

For authenticated requests, include:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer YOUR_ACCESS_TOKEN
```

The exact endpoint paths and request payloads are documented in Postman and in:

```text
documentation/api/API_REFERENCE.md
```

---

## API Response Format

The project uses a consistent JSON-response structure.

Example successful response:

```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {}
}
```

Example validation-error response:

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field": [
            "The field is required."
        ]
    }
}
```

The exact response may vary depending on the endpoint and returned API resource.

---

## Database

The project uses migrations, models, relations, factories, and seeders.

Main database entities include:

* Users
* Currencies
* Products
* Orders
* Order items
* Payment methods
* Payments

Main relationships include:

```text
User
└── has many Orders

Order
├── belongs to User
├── has many Order Items
└── has many Payments

Order Item
├── belongs to Order
└── belongs to Product

Product
└── belongs to Currency

Payment
├── belongs to Order
└── belongs to Payment Method
```

The exact relationships should be checked in the project's Eloquent models.

---

## Enums

Application statuses are represented using PHP enum classes.

Numeric enum values are used instead of storing status names directly in the database.

Examples include:

* Order status
* Payment status
* Other module-specific statuses

This approach provides:

* Consistent status values
* Type safety
* Centralized status definitions
* Easier validation
* Reduced string duplication

---

## Validation

Each module uses Laravel Form Request classes.

Example structure:

```text
Requests/
├── StoreProductRequest.php
├── UpdateProductRequest.php
├── StoreOrderRequest.php
└── ProcessPaymentRequest.php
```

Controllers use validated data only:

```php
$data = $request->validated();
```

This keeps validation logic outside controllers.

---

## API Resources

Laravel API Resources are used to control the JSON representation of models.

Example structure:

```text
Resources/
├── ProductResource.php
├── OrderResource.php
├── PaymentMethodResource.php
└── PaymentResource.php
```

Resources help prevent exposing unnecessary model fields and keep API responses consistent.

---

## Documentation Structure

The project documentation is stored in:

```text
documentation/
```

Current structure:

```text
documentation/
├── _config.yml
├── index.md
├── _includes/
├── assets/
├── getting-started/
│   └── INSTALLATION.md
├── project/
│   ├── PROJECT_SCOPE.md
│   ├── ARCHITECTURE.md
│   └── ASSUMPTIONS.md
├── modules/
│   ├── AUTHENTICATION_MODULE.md
│   ├── CURRENCY_MASTER_DATA.md
│   ├── PRODUCT_MASTER_DATA.md
│   ├── ORDER_MODULE.md
│   └── PAYMENT_MODULE.md
├── api/
│   └── API_REFERENCE.md
└── testing/
    └── TESTING.md
```

---

## Testing

The project includes feature tests and unit tests covering the Currency module.

### Test Structure

```text
tests/
├── Feature/
│   └── Modules/
│       └── Currency/
│           └── CurrencyControllerTest.php
└── Unit/
    └── Modules/
        └── Currency/
            ├── CurrencyServiceTest.php
            └── CurrencyRepositoryTest.php
```

### Test Types

**Feature tests** exercise the full HTTP stack end-to-end, including routing, middleware, JWT authentication, validation, service and repository layers, cache behavior, and JSON response shape.

**Unit tests** test individual classes in isolation. The service tests mock the repository using Mockery so no database is touched. The repository tests run against the test database directly using `RefreshDatabase`.

### Environment Setup for Testing

Create a dedicated test database and configure it in `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="tocaan_project_test"/>
<env name="CACHE_STORE" value="array"/>
```

Using `CACHE_STORE=array` in tests replaces the Redis cache with an in-memory array store, so no Redis connection is required to run the suite.

Alternatively, override only what is needed in `.env.testing`:

```env
DB_DATABASE=tocaan_project_test
CACHE_STORE=array
```

### Running the Tests

Run the full test suite:

```bash
php artisan test
```

Run only the Currency module:

```bash
php artisan test --filter=Currency
```

Run a specific test file:

```bash
php artisan test tests/Feature/Modules/Currency/CurrencyControllerTest.php
php artisan test tests/Unit/Modules/Currency/CurrencyServiceTest.php
php artisan test tests/Unit/Modules/Currency/CurrencyRepositoryTest.php
```

Run a single test method:

```bash
php artisan test --filter=it_activates_an_inactive_currency
```

Run with code coverage (requires Xdebug or PCOV):

```bash
php artisan test --coverage
php artisan test --coverage --min=80
```

### What Is Tested

#### CurrencyControllerTest (Feature)

| Area | Scenarios |
|---|---|
| `GET /currencies` | Returns all currencies ordered by code; empty collection; serves from Redis cache on repeated requests |
| `POST /currencies` | Creates with valid data; rejects duplicate code; rejects code longer than 3 characters; requires each mandatory field |
| `PATCH /currencies/{id}/toggle` | Activates an inactive currency; deactivates an active currency; invalidates Redis cache after toggle; returns 404 for a missing ID |

#### CurrencyServiceTest (Unit)

| Method | Scenarios |
|---|---|
| `index` | Fetches from repository on cache miss; returns cached value without hitting the repository |
| `add` | Delegates to repository and returns the created model; clears the Redis cache after creation |
| `toggleActive` | Delegates to repository and returns the updated model; clears the Redis cache after toggle |

#### CurrencyRepositoryTest (Unit)

| Method | Scenarios |
|---|---|
| `getAll` | Returns all records ordered by code; returns an empty collection when the table is empty; includes both active and inactive records |
| `create` | Persists the record and returns the model; defaults `is_active` to `true` from the database column default |
| `toggleActive` | Flips `true` to `false`; flips `false` to `true`; returns a refreshed model reflecting the new state; throws `ModelNotFoundException` for a missing ID; maintains correct state under successive toggles (transaction integrity) |

### Authentication in Tests

All feature tests authenticate using JWT. A user is created in `setUp` and a token is generated via:

```php
$this->token = JWTAuth::fromUser($user);
```

Each request includes the token as a Bearer header:

```php
$this->getJson($url, ['Authorization' => "Bearer {$this->token}"]);
```

### Notes

- `RefreshDatabase` rolls back all database changes after each test, so tests are fully isolated.
- The `CurrencyFactory` excludes the codes seeded by `CurrencySeeder` (`USD`, `EGP`, `EUR`, `GBP`) to avoid unique-constraint collisions when the seeder and factory are used in the same test.
- The repository's `create` method calls `->refresh()` after insert so that database-level column defaults (such as `is_active = true`) are reflected on the returned model immediately.

---

## Useful Artisan Commands

Clear application caches:

```bash
php artisan optimize:clear
```

Run database migrations:

```bash
php artisan migrate
```

Run migrations with seeders:

```bash
php artisan migrate --seed
```

Rebuild and seed the database:

```bash
php artisan migrate:fresh --seed
```

Display registered routes:

```bash
php artisan route:list
```

Display API routes:

```bash
php artisan route:list --path=api
```

Run tests:

```bash
php artisan test
```

Start the development server:

```bash
php artisan serve
```

---

## Project Principles

The project follows these engineering principles:

* Separation of concerns
* Dependency inversion
* Interface-based dependencies
* Thin controllers
* Reusable services
* Centralized database access
* Request validation
* Controlled API responses
* Extensible payment gateways
* Module isolation
* Numeric enum values
* Consistent code organization

---

## Assessment Notes

This project was developed as a technical assessment for Tocaan Company.

The implementation focuses on:

* Clean architecture
* High modularity
* Clear responsibility boundaries
* Maintainable business logic
* Extensible payment processing
* Consistent API design
* Testable services and repositories
* Clear technical documentation

---

## License

This project was created for technical-assessment purposes.

Usage, redistribution, and ownership are subject to the requirements of Tocaan Company and the repository owner.