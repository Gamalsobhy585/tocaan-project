<?php

namespace App\Modules\Currency\Repositories\Implementation;

use App\Models\Currency;
use App\Modules\Currency\Repositories\Interfaces\ICurrencyRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CurrencyRepository implements ICurrencyRepository
{
    public function getAll(): Collection
    {
        return Currency::query()
            ->orderBy('code')
            ->get();
    }

    public function create(array $data): Currency
    {
        return Currency::query()->create($data)->refresh(); 
    }

    public function toggleActive(int $currencyId): Currency
    {
        return DB::transaction(function () use ($currencyId) {
            $currency = Currency::query()
                ->lockForUpdate()
                ->findOrFail($currencyId);

            $currency->update([
                'is_active' => ! $currency->is_active,
            ]);

            return $currency->refresh();
        });
    }
}