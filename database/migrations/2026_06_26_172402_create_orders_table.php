<?php

use App\Enums\OrderStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number', 40)->unique();

            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->onDelete('restrict');

            $table->unsignedTinyInteger('status')
                ->default(OrderStatusEnum::Pending->value)
                ->comment('0 = pending, 1 = confirmed, 2 = cancelled')
                ->index();

            $table->decimal('total_amount', 15, 2)
                ->default(0);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
            $table->index('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};