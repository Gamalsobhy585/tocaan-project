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