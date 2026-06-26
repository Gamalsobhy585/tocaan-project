<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name_ar', 150);
            $table->string('name_en', 150);

            $table->string('code', 50)->unique();

            $table->unsignedInteger('quantity_in_stock')
                ->default(0);

            $table->decimal('unit_price', 15, 2)
                ->default(0);

            $table->timestamps();

            $table->index('name_ar');
            $table->index('name_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};