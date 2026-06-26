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