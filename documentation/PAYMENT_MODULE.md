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
   - Stores gateway responses and payment history.
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
- Payment-history actions use integer-backed enums.
- Payment methods are database master data, not enum values.
- A payment starts with status `0 = pending`.
- A gateway changes it to `1 = successful` or `2 = failed`.
- A cancelled or soft-deleted order cannot receive a new payment.
- Payment amount cannot exceed the remaining unpaid amount.
- Successful and currently pending amounts are considered when calculating the remaining amount.
- The backend reads the order currency and does not accept currency from the client.
- Payments and payment histories are not deleted.
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

## 4. Payment History Action Enum

| Value | Action | Meaning |
|---:|---|---|
| `0` | Created | Pending payment was created |
| `1` | Successful | Payment completed successfully |
| `2` | Failed | Payment failed |

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
| `GET` | `/api/payments/{payment}` | View one payment and its history |

---

## 6. Generate Files

```bash
php artisan generate:module PaymentMethod
php artisan generate:module Payment

php artisan make:model PaymentHistory

php artisan make:migration create_payment_methods_table
php artisan make:migration create_payments_table
php artisan make:migration create_payment_histories_table

php artisan make:seeder PaymentMethodSeeder
php artisan make:seeder PaymentSeeder

php artisan make:class Enums/PaymentStatusEnum
php artisan make:class Enums/PaymentHistoryActionEnum

php artisan make:class Modules/Payment/DTOs/PaymentGatewayContext
php artisan make:class Modules/Payment/DTOs/PaymentGatewayResult

php artisan make:class Modules/Payment/Gateways/Contracts/PaymentGatewayStrategy
php artisan make:class Modules/Payment/Gateways/PaymentGatewayRegistry
php artisan make:class Modules/Payment/Gateways/Strategies/CreditCardGatewayStrategy
php artisan make:class Modules/Payment/Gateways/Strategies/PayPalGatewayStrategy
php artisan make:class Modules/Payment/Exceptions/UnsupportedPaymentGatewayException
```

The custom module generator creates generic CRUD files. Replace the generated controllers, requests, resources, repositories, services, models, and routes with the files in this document.

---

## 7. Suggested Structure

```text
app/
├── Enums/
│   ├── PaymentStatusEnum.php
│   └── PaymentHistoryActionEnum.php
│
├── Models/
│   ├── PaymentMethod.php
│   ├── Payment.php
│   └── PaymentHistory.php
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
        │   └── PaymentHistoryResource.php
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
│   └── create_payment_histories_table.php
└── seeders/
    ├── PaymentMethodSeeder.php
    └── PaymentSeeder.php
```

---

# Database

## 8. Payment Methods Migration

File:

```text
database/migrations/xxxx_xx_xx_xxxxxx_create_payment_methods_table.php
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();

            $table->string('name_ar', 100);
            $table->string('name_en', 100);

            $table->string('code', 50)->unique();

            /*
             * Must match PaymentGatewayStrategy::key().
             * Examples: credit_card, paypal.
             */
            $table->string('strategy_key', 100)->index();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
```

---

## 9. Payments Migration

File:

```text
database/migrations/xxxx_xx_xx_xxxxxx_create_payments_table.php
```

```php
<?php

use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->string('payment_number', 50)->unique();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->restrictOnDelete();

            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->restrictOnDelete();

            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('status')
                ->default(PaymentStatusEnum::Pending->value)
                ->comment('0 = pending, 1 = successful, 2 = failed')
                ->index();

            $table->decimal('amount', 15, 2);

            $table->string('transaction_reference', 120)
                ->nullable()
                ->unique();

            /*
             * Optional client-generated key used to prevent duplicate requests.
             */
            $table->string('idempotency_key', 100)
                ->nullable()
                ->unique();

            $table->json('gateway_response')->nullable();

            $table->text('failure_reason')->nullable();

            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['payment_method_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
```

Payments do not use soft deletion. Financial records should remain available for audit.

---

## 10. Payment Histories Migration

File:

```text
database/migrations/xxxx_xx_xx_xxxxxx_create_payment_histories_table.php
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')
                ->constrained('payments')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('action')
                ->comment('0 = created, 1 = successful, 2 = failed');

            $table->unsignedTinyInteger('old_status')
                ->nullable()
                ->comment('0 = pending, 1 = successful, 2 = failed');

            $table->unsignedTinyInteger('new_status')
                ->nullable()
                ->comment('0 = pending, 1 = successful, 2 = failed');

            $table->json('details')->nullable();

            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('performed_by_name', 150)->nullable();

            $table->timestamps();

            $table->index(['payment_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};
```

---

# Enums

## 11. Payment Status Enum

File:

```text
app/Enums/PaymentStatusEnum.php
```

```php
<?php

namespace App\Enums;

enum PaymentStatusEnum: int
{
    /**
     * Payment was created and is waiting for the gateway result.
     */
    case Pending = 0;

    /**
     * Gateway processing completed successfully.
     */
    case Successful = 1;

    /**
     * Gateway processing failed.
     */
    case Failed = 2;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Successful => 'Successful',
            self::Failed => 'Failed',
        };
    }

    public static function values(): array
    {
        return array_map(
            fn (self $status): int => $status->value,
            self::cases()
        );
    }
}
```

---

## 12. Payment History Action Enum

File:

```text
app/Enums/PaymentHistoryActionEnum.php
```

```php
<?php

namespace App\Enums;

enum PaymentHistoryActionEnum: int
{
    /**
     * Pending payment record was created.
     */
    case Created = 0;

    /**
     * Gateway returned a successful result.
     */
    case Successful = 1;

    /**
     * Gateway returned a failed result or threw an exception.
     */
    case Failed = 2;

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Successful => 'Successful',
            self::Failed => 'Failed',
        };
    }
}
```

---

# Models

## 13. Payment Method Model

File:

```text
app/Models/PaymentMethod.php
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'code',
        'strategy_key',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
```

---

## 14. Payment Model

File:

```text
app/Models/Payment.php
```

```php
<?php

namespace App\Models;

use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'order_id',
        'payment_method_id',
        'currency_id',
        'status',
        'amount',
        'transaction_reference',
        'idempotency_key',
        'gateway_response',
        'failure_reason',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatusEnum::class,
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)
            ->withTrashed();
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'processed_by'
        );
    }

    public function histories(): HasMany
    {
        return $this->hasMany(PaymentHistory::class)
            ->latest('id');
    }
}
```

---

## 15. Payment History Model

File:

```text
app/Models/PaymentHistory.php
```

```php
<?php

namespace App\Models;

use App\Enums\PaymentHistoryActionEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'action',
        'old_status',
        'new_status',
        'details',
        'performed_by',
        'performed_by_name',
    ];

    protected function casts(): array
    {
        return [
            'action' => PaymentHistoryActionEnum::class,
            'old_status' => PaymentStatusEnum::class,
            'new_status' => PaymentStatusEnum::class,
            'details' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'performed_by'
        );
    }
}
```

---

# Strategy Pattern

## 16. Gateway Context DTO

File:

```text
app/Modules/Payment/DTOs/PaymentGatewayContext.php
```

```php
<?php

namespace App\Modules\Payment\DTOs;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;

final readonly class PaymentGatewayContext
{
    public function __construct(
        public Payment $payment,
        public Order $order,
        public PaymentMethod $paymentMethod,
        public array $payload = [],
    ) {
    }
}
```

---

## 17. Gateway Result DTO

File:

```text
app/Modules/Payment/DTOs/PaymentGatewayResult.php
```

```php
<?php

namespace App\Modules\Payment\DTOs;

final readonly class PaymentGatewayResult
{
    public function __construct(
        public bool $successful,
        public ?string $transactionReference = null,
        public array $response = [],
        public ?string $failureReason = null,
    ) {
    }

    public static function success(
        string $transactionReference,
        array $response = []
    ): self {
        return new self(
            successful: true,
            transactionReference: $transactionReference,
            response: $response,
        );
    }

    public static function failure(
        string $failureReason,
        array $response = []
    ): self {
        return new self(
            successful: false,
            response: $response,
            failureReason: $failureReason,
        );
    }
}
```

---

## 18. Gateway Strategy Contract

File:

```text
app/Modules/Payment/Gateways/Contracts/PaymentGatewayStrategy.php
```

```php
<?php

namespace App\Modules\Payment\Gateways\Contracts;

use App\Modules\Payment\DTOs\PaymentGatewayContext;
use App\Modules\Payment\DTOs\PaymentGatewayResult;

interface PaymentGatewayStrategy
{
    /**
     * Must match payment_methods.strategy_key.
     */
    public function key(): string;

    public function process(
        PaymentGatewayContext $context
    ): PaymentGatewayResult;
}
```

---

## 19. Unsupported Gateway Exception

File:

```text
app/Modules/Payment/Exceptions/UnsupportedPaymentGatewayException.php
```

```php
<?php

namespace App\Modules\Payment\Exceptions;

use RuntimeException;

class UnsupportedPaymentGatewayException extends RuntimeException
{
}
```

---

## 20. Gateway Registry

File:

```text
app/Modules/Payment/Gateways/PaymentGatewayRegistry.php
```

```php
<?php

namespace App\Modules\Payment\Gateways;

use App\Modules\Payment\Exceptions\UnsupportedPaymentGatewayException;
use App\Modules\Payment\Gateways\Contracts\PaymentGatewayStrategy;

class PaymentGatewayRegistry
{
    /**
     * @var array<string, PaymentGatewayStrategy>
     */
    private array $strategies = [];

    /**
     * @param iterable<PaymentGatewayStrategy> $strategies
     */
    public function __construct(iterable $strategies)
    {
        foreach ($strategies as $strategy) {
            $this->strategies[$strategy->key()] = $strategy;
        }
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->strategies);
    }

    public function resolve(string $key): PaymentGatewayStrategy
    {
        if (! $this->has($key)) {
            throw new UnsupportedPaymentGatewayException(
                "Payment gateway strategy [{$key}] is not registered."
            );
        }

        return $this->strategies[$key];
    }

    public function registeredKeys(): array
    {
        return array_keys($this->strategies);
    }
}
```

---

## 21. Credit Card Strategy

File:

```text
app/Modules/Payment/Gateways/Strategies/CreditCardGatewayStrategy.php
```

```php
<?php

namespace App\Modules\Payment\Gateways\Strategies;

use App\Modules\Payment\DTOs\PaymentGatewayContext;
use App\Modules\Payment\DTOs\PaymentGatewayResult;
use App\Modules\Payment\Gateways\Contracts\PaymentGatewayStrategy;
use Illuminate\Support\Str;

class CreditCardGatewayStrategy implements PaymentGatewayStrategy
{
    public function key(): string
    {
        return 'credit_card';
    }

    public function process(
        PaymentGatewayContext $context
    ): PaymentGatewayResult {
        /*
         * Simulation only.
         * Replace this block with the real provider SDK later.
         */
        $shouldSucceed = (bool) (
            $context->payload['simulate_success'] ?? true
        );

        if (! $shouldSucceed) {
            return PaymentGatewayResult::failure(
                failureReason: 'Credit card payment was declined.',
                response: [
                    'gateway' => $this->key(),
                    'code' => 'CARD_DECLINED',
                ]
            );
        }

        return PaymentGatewayResult::success(
            transactionReference: 'CC-' . Str::upper(
                Str::random(20)
            ),
            response: [
                'gateway' => $this->key(),
                'code' => 'APPROVED',
            ]
        );
    }
}
```

---

## 22. PayPal Strategy

File:

```text
app/Modules/Payment/Gateways/Strategies/PayPalGatewayStrategy.php
```

```php
<?php

namespace App\Modules\Payment\Gateways\Strategies;

use App\Modules\Payment\DTOs\PaymentGatewayContext;
use App\Modules\Payment\DTOs\PaymentGatewayResult;
use App\Modules\Payment\Gateways\Contracts\PaymentGatewayStrategy;
use Illuminate\Support\Str;

class PayPalGatewayStrategy implements PaymentGatewayStrategy
{
    public function key(): string
    {
        return 'paypal';
    }

    public function process(
        PaymentGatewayContext $context
    ): PaymentGatewayResult {
        /*
         * Simulation only.
         * Replace this block with the real PayPal SDK later.
         */
        $shouldSucceed = (bool) (
            $context->payload['simulate_success'] ?? true
        );

        if (! $shouldSucceed) {
            return PaymentGatewayResult::failure(
                failureReason: 'PayPal payment was rejected.',
                response: [
                    'gateway' => $this->key(),
                    'state' => 'rejected',
                ]
            );
        }

        return PaymentGatewayResult::success(
            transactionReference: 'PP-' . Str::upper(
                Str::random(20)
            ),
            response: [
                'gateway' => $this->key(),
                'state' => 'approved',
            ]
        );
    }
}
```

---

# Payment Method Module

## 23. Payment Method Repository Interface

File:

```text
app/Modules/PaymentMethod/Repositories/Interfaces/IPaymentMethodRepository.php
```

```php
<?php

namespace App\Modules\PaymentMethod\Repositories\Interfaces;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;

interface IPaymentMethodRepository
{
    public function getAll(): Collection;

    public function create(array $data): PaymentMethod;

    public function toggleActive(
        PaymentMethod $paymentMethod
    ): PaymentMethod;
}
```

---

## 24. Payment Method Repository

File:

```text
app/Modules/PaymentMethod/Repositories/Implementation/PaymentMethodRepository.php
```

```php
<?php

namespace App\Modules\PaymentMethod\Repositories\Implementation;

use App\Models\PaymentMethod;
use App\Modules\PaymentMethod\Repositories\Interfaces\IPaymentMethodRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PaymentMethodRepository implements IPaymentMethodRepository
{
    public function getAll(): Collection
    {
        return PaymentMethod::query()
            ->orderBy('name_en')
            ->get();
    }

    public function create(array $data): PaymentMethod
    {
        return PaymentMethod::query()->create($data);
    }

    public function toggleActive(
        PaymentMethod $paymentMethod
    ): PaymentMethod {
        return DB::transaction(function () use ($paymentMethod) {
            $lockedMethod = PaymentMethod::query()
                ->lockForUpdate()
                ->findOrFail($paymentMethod->id);

            $lockedMethod->update([
                'is_active' => ! $lockedMethod->is_active,
            ]);

            return $lockedMethod->refresh();
        });
    }
}
```

---

## 25. Payment Method Service Interface

File:

```text
app/Modules/PaymentMethod/Services/Interfaces/IPaymentMethodService.php
```

```php
<?php

namespace App\Modules\PaymentMethod\Services\Interfaces;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;

interface IPaymentMethodService
{
    public function index(): Collection;

    public function add(array $data): PaymentMethod;

    public function toggleActive(
        PaymentMethod $paymentMethod
    ): PaymentMethod;
}
```

---

## 26. Payment Method Service

File:

```text
app/Modules/PaymentMethod/Services/PaymentMethodService.php
```

```php
<?php

namespace App\Modules\PaymentMethod\Services;

use App\Models\PaymentMethod;
use App\Modules\Payment\Gateways\PaymentGatewayRegistry;
use App\Modules\PaymentMethod\Repositories\Interfaces\IPaymentMethodRepository;
use App\Modules\PaymentMethod\Services\Interfaces\IPaymentMethodService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PaymentMethodService implements IPaymentMethodService
{
    public function __construct(
        private readonly IPaymentMethodRepository $repository,
        private readonly PaymentGatewayRegistry $registry,
    ) {
    }

    public function index(): Collection
    {
        return $this->repository->getAll();
    }

    public function add(array $data): PaymentMethod
    {
        if (! $this->registry->has($data['strategy_key'])) {
            throw ValidationException::withMessages([
                'strategy_key' => sprintf(
                    'The strategy [%s] is not registered. Registered strategies: %s',
                    $data['strategy_key'],
                    implode(', ', $this->registry->registeredKeys())
                ),
            ]);
        }

        return $this->repository->create($data);
    }

    public function toggleActive(
        PaymentMethod $paymentMethod
    ): PaymentMethod {
        return $this->repository->toggleActive(
            $paymentMethod
        );
    }
}
```

---

## 27. Store Payment Method Request

File:

```text
app/Modules/PaymentMethod/Requests/StorePaymentMethodRequest.php
```

```php
<?php

namespace App\Modules\PaymentMethod\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtolower(
                trim((string) $this->input('code'))
            ),
            'strategy_key' => strtolower(
                trim((string) $this->input('strategy_key'))
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'name_ar' => [
                'required',
                'string',
                'max:100',
            ],

            'name_en' => [
                'required',
                'string',
                'max:100',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('payment_methods', 'code'),
            ],

            'strategy_key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_-]+$/',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
```

---

## 28. Payment Method Resource

File:

```text
app/Modules/PaymentMethod/Resources/PaymentMethodResource.php
```

```php
<?php

namespace App\Modules\PaymentMethod\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'code' => $this->code,
            'strategy_key' => $this->strategy_key,
            'is_active' => $this->is_active,
        ];
    }
}
```

---

## 29. Payment Method Controller

File:

```text
app/Modules/PaymentMethod/Controllers/PaymentMethodController.php
```

```php
<?php

namespace App\Modules\PaymentMethod\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Modules\PaymentMethod\Requests\StorePaymentMethodRequest;
use App\Modules\PaymentMethod\Resources\PaymentMethodResource;
use App\Modules\PaymentMethod\Services\Interfaces\IPaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly IPaymentMethodService $service
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        return PaymentMethodResource::collection(
            $this->service->index()
        );
    }

    public function add(
        StorePaymentMethodRequest $request
    ): JsonResponse {
        $paymentMethod = $this->service->add(
            $request->validated()
        );

        return response()->json([
            'message' => 'Payment method added successfully.',
            'data' => new PaymentMethodResource($paymentMethod),
        ], 201);
    }

    public function toggleActive(
        PaymentMethod $paymentMethod
    ): JsonResponse {
        $paymentMethod = $this->service->toggleActive(
            $paymentMethod
        );

        return response()->json([
            'message' => $paymentMethod->is_active
                ? 'Payment method activated successfully.'
                : 'Payment method deactivated successfully.',
            'data' => new PaymentMethodResource($paymentMethod),
        ]);
    }
}
```

---

## 30. Payment Method Routes

File:

```text
app/Modules/PaymentMethod/Routes/api.php
```

```php
<?php

use App\Modules\PaymentMethod\Controllers\PaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')
    ->prefix('payment-methods')
    ->controller(PaymentMethodController::class)
    ->group(function () {
        Route::get('', 'index')
            ->name('payment-methods.index');

        Route::post('', 'add')
            ->name('payment-methods.add');

        Route::patch('{paymentMethod}/toggle-active', 'toggleActive')
            ->name('payment-methods.toggle-active');
    });
```

---

# Payment Module

## 31. Payment Repository Interface

File:

```text
app/Modules/Payment/Repositories/Interfaces/IPaymentRepository.php
```

```php
<?php

namespace App\Modules\Payment\Repositories\Interfaces;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IPaymentRepository
{
    public function paginate(array $filters): LengthAwarePaginator;

    public function paginateForOrder(
        int $orderId,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function create(array $data): Payment;

    public function lockById(int $paymentId): Payment;

    public function update(
        Payment $payment,
        array $data
    ): Payment;

    public function findWithDetails(int $paymentId): Payment;
}
```

---

## 32. Payment Repository

File:

```text
app/Modules/Payment/Repositories/Implementation/PaymentRepository.php
```

```php
<?php

namespace App\Modules\Payment\Repositories\Implementation;

use App\Models\Payment;
use App\Modules\Payment\Repositories\Interfaces\IPaymentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRepository implements IPaymentRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(
            max((int) ($filters['per_page'] ?? 15), 1),
            100
        );

        return Payment::query()
            ->with([
                'order:id,order_number',
                'paymentMethod:id,name_ar,name_en,code,strategy_key',
                'currency:id,name_ar,name_en,code',
                'processor:id,name',
            ])
            ->when(
                isset($filters['order_id']),
                fn ($query) => $query->where(
                    'order_id',
                    $filters['order_id']
                )
            )
            ->when(
                isset($filters['status']),
                fn ($query) => $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->when(
                isset($filters['payment_method_id']),
                fn ($query) => $query->where(
                    'payment_method_id',
                    $filters['payment_method_id']
                )
            )
            ->when(
                filled($filters['date_from'] ?? null),
                fn ($query) => $query->whereDate(
                    'created_at',
                    '>=',
                    $filters['date_from']
                )
            )
            ->when(
                filled($filters['date_to'] ?? null),
                fn ($query) => $query->whereDate(
                    'created_at',
                    '<=',
                    $filters['date_to']
                )
            )
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForOrder(
        int $orderId,
        int $perPage = 15
    ): LengthAwarePaginator {
        $perPage = min(max($perPage, 1), 100);

        return Payment::query()
            ->where('order_id', $orderId)
            ->with([
                'paymentMethod:id,name_ar,name_en,code,strategy_key',
                'currency:id,name_ar,name_en,code',
                'processor:id,name',
            ])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Payment
    {
        return Payment::query()->create($data);
    }

    public function lockById(int $paymentId): Payment
    {
        return Payment::query()
            ->lockForUpdate()
            ->findOrFail($paymentId);
    }

    public function update(
        Payment $payment,
        array $data
    ): Payment {
        $payment->update($data);

        return $payment->refresh();
    }

    public function findWithDetails(int $paymentId): Payment
    {
        return Payment::query()
            ->with([
                'order',
                'paymentMethod',
                'currency',
                'processor:id,name',
                'histories.performer:id,name',
            ])
            ->findOrFail($paymentId);
    }
}
```

---

## 33. Payment Service Interface

File:

```text
app/Modules/Payment/Services/Interfaces/IPaymentService.php
```

```php
<?php

namespace App\Modules\Payment\Services\Interfaces;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IPaymentService
{
    public function index(array $filters): LengthAwarePaginator;

    public function forOrder(
        int $orderId,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function show(int $paymentId): Payment;

    public function process(
        int $orderId,
        array $data,
        int $actorId
    ): Payment;
}
```

---

## 34. Payment Service

File:

```text
app/Modules/Payment/Services/PaymentService.php
```

```php
<?php

namespace App\Modules\Payment\Services;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentHistoryActionEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Modules\Payment\DTOs\PaymentGatewayContext;
use App\Modules\Payment\DTOs\PaymentGatewayResult;
use App\Modules\Payment\Gateways\PaymentGatewayRegistry;
use App\Modules\Payment\Repositories\Interfaces\IPaymentRepository;
use App\Modules\Payment\Services\Interfaces\IPaymentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentService implements IPaymentService
{
    public function __construct(
        private readonly IPaymentRepository $repository,
        private readonly PaymentGatewayRegistry $gatewayRegistry,
    ) {
    }

    public function index(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function forOrder(
        int $orderId,
        int $perPage = 15
    ): LengthAwarePaginator {
        Order::query()
            ->withTrashed()
            ->findOrFail($orderId);

        return $this->repository->paginateForOrder(
            orderId: $orderId,
            perPage: $perPage
        );
    }

    public function show(int $paymentId): Payment
    {
        return $this->repository->findWithDetails(
            $paymentId
        );
    }

    public function process(
        int $orderId,
        array $data,
        int $actorId
    ): Payment {
        $prepared = DB::transaction(function () use (
            $orderId,
            $data,
            $actorId
        ) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($orderId);

            if (
                $order->status === OrderStatusEnum::Cancelled
                || $order->trashed()
            ) {
                throw ValidationException::withMessages([
                    'order' => 'A cancelled order cannot receive a payment.',
                ]);
            }

            $paymentMethod = PaymentMethod::query()
                ->whereKey($data['payment_method_id'])
                ->where('is_active', true)
                ->firstOrFail();

            /*
             * Fail before creating the payment when the strategy
             * has not been registered in the application.
             */
            $this->gatewayRegistry->resolve(
                $paymentMethod->strategy_key
            );

            $successfulAmount = (float) Payment::query()
                ->where('order_id', $order->id)
                ->where(
                    'status',
                    PaymentStatusEnum::Successful->value
                )
                ->sum('amount');

            $pendingAmount = (float) Payment::query()
                ->where('order_id', $order->id)
                ->where(
                    'status',
                    PaymentStatusEnum::Pending->value
                )
                ->sum('amount');

            $remainingAmount = round(
                (float) $order->total_amount
                    - $successfulAmount
                    - $pendingAmount,
                2
            );

            if ($remainingAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'This order has no remaining amount to pay.',
                ]);
            }

            $amount = array_key_exists('amount', $data)
                ? round((float) $data['amount'], 2)
                : $remainingAmount;

            if ($amount <= 0 || $amount > $remainingAmount) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'The payment amount must be greater than zero and cannot exceed %.2f.',
                        $remainingAmount
                    ),
                ]);
            }

            $payment = $this->repository->create([
                'payment_number' => $this->generatePaymentNumber(),
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethod->id,
                'currency_id' => $order->currency_id,
                'status' => PaymentStatusEnum::Pending,
                'amount' => $amount,
                'idempotency_key' => $data['idempotency_key']
                    ?? null,
                'processed_by' => $actorId,
            ]);

            $payment->histories()->create([
                'action' => PaymentHistoryActionEnum::Created,
                'old_status' => null,
                'new_status' => PaymentStatusEnum::Pending,
                'details' => [
                    'amount' => $amount,
                    'payment_method_id' => $paymentMethod->id,
                    'strategy_key' => $paymentMethod->strategy_key,
                ],
                'performed_by' => $actorId,
                'performed_by_name' => $this->actorName($actorId),
            ]);

            return [
                'payment_id' => $payment->id,
                'order' => $order,
                'payment_method' => $paymentMethod,
                'gateway_payload' => $data['gateway_payload'] ?? [],
            ];
        });

        $payment = Payment::query()->findOrFail(
            $prepared['payment_id']
        );

        $strategy = $this->gatewayRegistry->resolve(
            $prepared['payment_method']->strategy_key
        );

        try {
            $result = $strategy->process(
                new PaymentGatewayContext(
                    payment: $payment,
                    order: $prepared['order'],
                    paymentMethod: $prepared['payment_method'],
                    payload: $prepared['gateway_payload'],
                )
            );
        } catch (Throwable $exception) {
            report($exception);

            $result = PaymentGatewayResult::failure(
                failureReason: 'The payment gateway could not process the request.',
                response: [
                    'exception' => class_basename($exception),
                ]
            );
        }

        return $this->finalizePayment(
            paymentId: $payment->id,
            result: $result,
            actorId: $actorId
        );
    }

    private function finalizePayment(
        int $paymentId,
        PaymentGatewayResult $result,
        int $actorId
    ): Payment {
        DB::transaction(function () use (
            $paymentId,
            $result,
            $actorId
        ) {
            $payment = $this->repository->lockById(
                $paymentId
            );

            if ($payment->status !== PaymentStatusEnum::Pending) {
                throw ValidationException::withMessages([
                    'payment' => 'This payment has already been finalized.',
                ]);
            }

            $newStatus = $result->successful
                ? PaymentStatusEnum::Successful
                : PaymentStatusEnum::Failed;

            $this->repository->update($payment, [
                'status' => $newStatus,
                'transaction_reference' =>
                    $result->transactionReference,
                'gateway_response' => $result->response,
                'failure_reason' => $result->failureReason,
                'processed_by' => $actorId,
                'processed_at' => now(),
            ]);

            $payment->histories()->create([
                'action' => $result->successful
                    ? PaymentHistoryActionEnum::Successful
                    : PaymentHistoryActionEnum::Failed,
                'old_status' => PaymentStatusEnum::Pending,
                'new_status' => $newStatus,
                'details' => [
                    'transaction_reference' =>
                        $result->transactionReference,
                    'gateway_response' => $result->response,
                    'failure_reason' => $result->failureReason,
                ],
                'performed_by' => $actorId,
                'performed_by_name' => $this->actorName(
                    $actorId
                ),
            ]);
        });

        return $this->repository->findWithDetails(
            $paymentId
        );
    }

    private function generatePaymentNumber(): string
    {
        return 'PAY-' . Str::upper(
            (string) Str::ulid()
        );
    }

    private function actorName(int $actorId): ?string
    {
        return User::query()
            ->whereKey($actorId)
            ->value('name');
    }
}
```

The gateway call is outside the initial database transaction. This prevents holding order and payment locks while an external provider is processing.

---

## 35. Process Payment Request

File:

```text
app/Modules/Payment/Requests/ProcessPaymentRequest.php
```

```php
<?php

namespace App\Modules\Payment\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('payment_methods', 'id')
                    ->where(
                        fn (Builder $query) => $query->where(
                            'is_active',
                            true
                        )
                    ),
            ],

            /*
             * When omitted, the service pays the full remaining amount.
             */
            'amount' => [
                'nullable',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'idempotency_key' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('payments', 'idempotency_key'),
            ],

            'gateway_payload' => [
                'sometimes',
                'array',
            ],

            /*
             * Simulation field inside gateway_payload.
             */
            'gateway_payload.simulate_success' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
```

---

## 36. Index Payments Request

File:

```text
app/Modules/Payment/Requests/IndexPaymentRequest.php
```

```php
<?php

namespace App\Modules\Payment\Requests;

use App\Enums\PaymentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => [
                'nullable',
                'integer',
                'exists:orders,id',
            ],

            'status' => [
                'nullable',
                'integer',
                Rule::in(PaymentStatusEnum::values()),
            ],

            'payment_method_id' => [
                'nullable',
                'integer',
                'exists:payment_methods,id',
            ],

            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
```

---

## 37. Payment History Resource

File:

```text
app/Modules/Payment/Resources/PaymentHistoryResource.php
```

```php
<?php

namespace App\Modules\Payment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'action' => [
                'value' => $this->action->value,
                'label' => $this->action->label(),
            ],

            'old_status' => $this->old_status
                ? [
                    'value' => $this->old_status->value,
                    'label' => $this->old_status->label(),
                ]
                : null,

            'new_status' => $this->new_status
                ? [
                    'value' => $this->new_status->value,
                    'label' => $this->new_status->label(),
                ]
                : null,

            'details' => $this->details,

            'performed_by' => [
                'id' => $this->performed_by,
                'name' => $this->performer?->name
                    ?? $this->performed_by_name,
            ],

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
```

---

## 38. Payment Resource

File:

```text
app/Modules/Payment/Resources/PaymentResource.php
```

```php
<?php

namespace App\Modules\Payment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,

            'order' => $this->whenLoaded(
                'order',
                fn () => $this->order
                    ? [
                        'id' => $this->order->id,
                        'order_number' => $this->order->order_number,
                    ]
                    : null
            ),

            'payment_method' => $this->whenLoaded(
                'paymentMethod',
                fn () => [
                    'id' => $this->paymentMethod->id,
                    'name_ar' => $this->paymentMethod->name_ar,
                    'name_en' => $this->paymentMethod->name_en,
                    'code' => $this->paymentMethod->code,
                ]
            ),

            'currency' => $this->whenLoaded(
                'currency',
                fn () => [
                    'id' => $this->currency->id,
                    'name_ar' => $this->currency->name_ar,
                    'name_en' => $this->currency->name_en,
                    'code' => $this->currency->code,
                ]
            ),

            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],

            'amount' => (float) $this->amount,
            'transaction_reference' =>
                $this->transaction_reference,
            'gateway_response' => $this->gateway_response,
            'failure_reason' => $this->failure_reason,

            'processed_by' => $this->whenLoaded(
                'processor',
                fn () => $this->processor
                    ? [
                        'id' => $this->processor->id,
                        'name' => $this->processor->name,
                    ]
                    : null
            ),

            'processed_at' =>
                $this->processed_at?->toISOString(),

            'history' => PaymentHistoryResource::collection(
                $this->whenLoaded('histories')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

---

## 39. Payment Controller

File:

```text
app/Modules/Payment/Controllers/PaymentController.php
```

```php
<?php

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Modules\Payment\Requests\IndexPaymentRequest;
use App\Modules\Payment\Requests\ProcessPaymentRequest;
use App\Modules\Payment\Resources\PaymentResource;
use App\Modules\Payment\Services\Interfaces\IPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function __construct(
        private readonly IPaymentService $service
    ) {
    }

    public function index(
        IndexPaymentRequest $request
    ): AnonymousResourceCollection {
        return PaymentResource::collection(
            $this->service->index(
                $request->validated()
            )
        );
    }

    public function forOrder(
        Request $request,
        int $order
    ): AnonymousResourceCollection {
        $validated = $request->validate([
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        return PaymentResource::collection(
            $this->service->forOrder(
                orderId: $order,
                perPage: (int) (
                    $validated['per_page'] ?? 15
                )
            )
        );
    }

    public function show(Payment $payment): PaymentResource
    {
        return new PaymentResource(
            $this->service->show($payment->id)
        );
    }

    public function process(
        ProcessPaymentRequest $request,
        int $order
    ): JsonResponse {
        $payment = $this->service->process(
            orderId: $order,
            data: $request->validated(),
            actorId: (int) $request
                ->user()
                ->getAuthIdentifier()
        );

        return response()->json([
            'message' => $payment->status->label()
                === 'Successful'
                ? 'Payment processed successfully.'
                : 'Payment processing failed.',
            'data' => new PaymentResource($payment),
        ], $payment->status->value === 1 ? 201 : 422);
    }
}
```

A cleaner status check can use the enum directly:

```php
use App\Enums\PaymentStatusEnum;

$payment->status === PaymentStatusEnum::Successful
```

---

## 40. Payment Routes

File:

```text
app/Modules/Payment/Routes/api.php
```

```php
<?php

use App\Modules\Payment\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')
    ->group(function () {
        Route::get('payments', [
            PaymentController::class,
            'index',
        ])->name('payments.index');

        Route::get('payments/{payment}', [
            PaymentController::class,
            'show',
        ])->name('payments.show');

        Route::get('orders/{order}/payments', [
            PaymentController::class,
            'forOrder',
        ])->name('orders.payments.index');

        Route::post('orders/{order}/payments/process', [
            PaymentController::class,
            'process',
        ])->name('orders.payments.process');
    });
```

Ensure the main `routes/api.php` contains:

```php
require app_path('Modules/PaymentMethod/Routes/api.php');
require app_path('Modules/Payment/Routes/api.php');
```

---

# Dependency Injection

## 41. Payment Service Provider

File:

```text
app/Modules/Payment/Providers/PaymentServiceProvider.php
```

```php
<?php

namespace App\Modules\Payment\Providers;

use App\Modules\Payment\Gateways\PaymentGatewayRegistry;
use App\Modules\Payment\Gateways\Strategies\CreditCardGatewayStrategy;
use App\Modules\Payment\Gateways\Strategies\PayPalGatewayStrategy;
use App\Modules\Payment\Repositories\Implementation\PaymentRepository;
use App\Modules\Payment\Repositories\Interfaces\IPaymentRepository;
use App\Modules\Payment\Services\Interfaces\IPaymentService;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\PaymentMethod\Repositories\Implementation\PaymentMethodRepository;
use App\Modules\PaymentMethod\Repositories\Interfaces\IPaymentMethodRepository;
use App\Modules\PaymentMethod\Services\Interfaces\IPaymentMethodService;
use App\Modules\PaymentMethod\Services\PaymentMethodService;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            IPaymentMethodRepository::class,
            PaymentMethodRepository::class
        );

        $this->app->bind(
            IPaymentMethodService::class,
            PaymentMethodService::class
        );

        $this->app->bind(
            IPaymentRepository::class,
            PaymentRepository::class
        );

        $this->app->bind(
            IPaymentService::class,
            PaymentService::class
        );

        $this->app->tag([
            CreditCardGatewayStrategy::class,
            PayPalGatewayStrategy::class,
        ], 'payment.gateway.strategies');

        $this->app->singleton(
            PaymentGatewayRegistry::class,
            fn ($app) => new PaymentGatewayRegistry(
                $app->tagged('payment.gateway.strategies')
            )
        );
    }

    public function boot(): void
    {
        //
    }
}
```

Register the provider in:

```text
bootstrap/providers.php
```

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Payment\Providers\PaymentServiceProvider::class,
];
```

---

# Seeders

## 42. Payment Method Seeder

File:

```text
database/seeders/PaymentMethodSeeder.php
```

```php
<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name_ar' => 'بطاقة ائتمان',
                'name_en' => 'Credit Card',
                'code' => 'credit_card',
                'strategy_key' => 'credit_card',
                'is_active' => true,
            ],
            [
                'name_ar' => 'باي بال',
                'name_en' => 'PayPal',
                'code' => 'paypal',
                'strategy_key' => 'paypal',
                'is_active' => true,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}
```

---

## 43. Payment Seeder

The payment seeder uses the payment service so that status changes and history are created correctly.

File:

```text
database/seeders/PaymentSeeder.php
```

```php
<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Modules\Payment\Services\Interfaces\IPaymentService;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        if (! $user) {
            $this->command?->warn(
                'PaymentSeeder skipped: no users found.'
            );

            return;
        }

        $methods = PaymentMethod::query()
            ->where('is_active', true)
            ->get();

        if ($methods->isEmpty()) {
            $this->command?->warn(
                'PaymentSeeder skipped: no active payment methods found.'
            );

            return;
        }

        $orders = Order::query()
            ->whereDoesntHave('payments')
            ->limit(10)
            ->get();

        if ($orders->isEmpty()) {
            $this->command?->warn(
                'PaymentSeeder skipped: no payable orders found.'
            );

            return;
        }

        /** @var IPaymentService $service */
        $service = app(IPaymentService::class);

        foreach ($orders as $index => $order) {
            $method = $methods[$index % $methods->count()];

            $service->process(
                orderId: $order->id,
                data: [
                    'payment_method_id' => $method->id,
                    'gateway_payload' => [
                        'simulate_success' => $index % 3 !== 0,
                    ],
                ],
                actorId: $user->id
            );
        }
    }
}
```

Add the payments relation to the Order model:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function payments(): HasMany
{
    return $this->hasMany(Payment::class);
}
```

Register seeders in this order:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
            PaymentMethodSeeder::class,
            PaymentSeeder::class,
        ]);
    }
}
```

---

# API Examples

## 44. List Payment Methods

```http
GET /api/payment-methods
Authorization: Bearer JWT_TOKEN
```

---

## 45. Add Payment Method Metadata

The strategy class must already be registered.

```http
POST /api/payment-methods
Authorization: Bearer JWT_TOKEN
Content-Type: application/json
```

```json
{
  "name_ar": "بوابة اختبار",
  "name_en": "Test Gateway",
  "code": "test_gateway",
  "strategy_key": "test_gateway",
  "is_active": true
}
```

This request fails when `test_gateway` is not registered in `PaymentGatewayRegistry`.

---

## 46. Process Successful Payment

```http
POST /api/orders/1/payments/process
Authorization: Bearer JWT_TOKEN
Content-Type: application/json
```

```json
{
  "payment_method_id": 1,
  "gateway_payload": {
    "simulate_success": true
  }
}
```

When `amount` is omitted, the backend pays the full remaining order amount.

---

## 47. Process Partial Payment

```json
{
  "payment_method_id": 1,
  "amount": 500,
  "idempotency_key": "order-1-payment-attempt-1",
  "gateway_payload": {
    "simulate_success": true
  }
}
```

---

## 48. Simulate Failed Payment

```json
{
  "payment_method_id": 2,
  "gateway_payload": {
    "simulate_success": false
  }
}
```

---

## 49. View All Payments

```http
GET /api/payments?page=1&per_page=15
Authorization: Bearer JWT_TOKEN
```

Filter successful payments:

```text
GET /api/payments?status=1
```

Filter failed payments:

```text
GET /api/payments?status=2
```

Filter by method:

```text
GET /api/payments?payment_method_id=1
```

Filter by date:

```text
GET /api/payments?date_from=2026-01-01&date_to=2026-12-31
```

---

## 50. View Payments for One Order

```http
GET /api/orders/1/payments?per_page=15
Authorization: Bearer JWT_TOKEN
```

---

## 51. View One Payment

```http
GET /api/payments/1
Authorization: Bearer JWT_TOKEN
```

The response includes payment history.

---

# Adding a New Gateway

## 52. Example: Stripe

Create:

```text
app/Modules/Payment/Gateways/Strategies/StripeGatewayStrategy.php
```

```php
<?php

namespace App\Modules\Payment\Gateways\Strategies;

use App\Modules\Payment\DTOs\PaymentGatewayContext;
use App\Modules\Payment\DTOs\PaymentGatewayResult;
use App\Modules\Payment\Gateways\Contracts\PaymentGatewayStrategy;
use Illuminate\Support\Str;

class StripeGatewayStrategy implements PaymentGatewayStrategy
{
    public function key(): string
    {
        return 'stripe';
    }

    public function process(
        PaymentGatewayContext $context
    ): PaymentGatewayResult {
        /*
         * Replace with the Stripe SDK.
         */
        return PaymentGatewayResult::success(
            transactionReference: 'STR-' . Str::upper(
                Str::random(20)
            ),
            response: [
                'gateway' => 'stripe',
                'status' => 'succeeded',
            ]
        );
    }
}
```

Register only the new class in `PaymentServiceProvider`:

```php
$this->app->tag([
    CreditCardGatewayStrategy::class,
    PayPalGatewayStrategy::class,
    StripeGatewayStrategy::class,
], 'payment.gateway.strategies');
```

Add the database record:

```php
PaymentMethod::query()->updateOrCreate(
    ['code' => 'stripe'],
    [
        'name_ar' => 'سترايب',
        'name_en' => 'Stripe',
        'strategy_key' => 'stripe',
        'is_active' => true,
    ]
);
```

No changes are required in:

- `PaymentController`
- `PaymentService`
- `PaymentRepository`
- Existing gateway strategies

---

# Run and Verify

## 53. Run Migrations and Seeders

```bash
php artisan migrate

php artisan db:seed --class=PaymentMethodSeeder
php artisan db:seed --class=PaymentSeeder

php artisan optimize:clear
```

---

## 54. Check Routes

```bash
php artisan route:list --path=payment
```

Expected routes:

```text
GET       api/payment-methods
POST      api/payment-methods
PATCH     api/payment-methods/{paymentMethod}/toggle-active

GET       api/payments
GET       api/payments/{payment}
GET       api/orders/{order}/payments
POST      api/orders/{order}/payments/process
```

---

# Important Production Notes

## 55. Do Not Store Sensitive Payment Data

Do not store:

- Full credit-card number.
- CVV.
- PayPal passwords.
- Gateway secret keys.
- Raw authentication tokens.

Store only safe provider references and sanitized response data.

## 56. Webhooks

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
6. Add payment history.
7. Return a successful HTTP response.

## 57. Idempotency

Use an `idempotency_key` to prevent the same client action from creating duplicate payment attempts.

The database unique constraint protects against concurrent duplicate requests.

## 58. Financial Records

Do not soft-delete or permanently delete payments and histories.

Create refund and void workflows as separate operations rather than changing or deleting old financial records.
