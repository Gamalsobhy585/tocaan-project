<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            /*
             * Nullable because Product currently supports permanent deletion.
             * The order item snapshots below preserve the old product data.
             */
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            $table->string('product_name_ar', 150);
            $table->string('product_name_en', 150);
            $table->string('product_code', 50);

            $table->unsignedInteger('quantity');

            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);

            $table->timestamps();

            $table->index(['order_id', 'product_id']);
            $table->index('product_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};