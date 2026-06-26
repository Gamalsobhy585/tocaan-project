<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('action')
                ->comment('0 = created, 1 = updated, 2 = cancelled');

            $table->unsignedTinyInteger('old_status')
                ->nullable()
                ->comment('0 = pending, 1 = confirmed, 2 = cancelled');

            $table->unsignedTinyInteger('new_status')
                ->nullable()
                ->comment('0 = pending, 1 = confirmed, 2 = cancelled');

            $table->json('changes')->nullable();

            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Keeps the actor name even when the user is removed later.
             */

            $table->timestamps();

            $table->index(['order_id', 'action']);
            $table->index('performed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_histories');
    }
};