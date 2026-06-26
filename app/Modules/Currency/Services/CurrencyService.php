<?php

namespace App\Modules\Currency\Services;

use App\Models\Currency;
use App\Modules\Currency\Repositories\Interfaces\ICurrencyRepository;
use App\Modules\Currency\Services\Interfaces\ICurrencyService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CurrencyService implements ICurrencyService
{
    private const CACHE_KEY = 'master_data:currencies:all';

    public function __construct(
        private readonly ICurrencyRepository $repository
    ) {
    }

    public function index(): Collection
    {
        return Cache::store('redis')->rememberForever(
            self::CACHE_KEY,
            fn (): Collection => $this->repository->getAll()
        );
    }

    public function add(array $data): Currency
    {
        $currency = $this->repository->create($data);

        $this->clearCache();

        return $currency;
    }

    public function toggleActive(int $currencyId): Currency
    {
        $currency = $this->repository->toggleActive($currencyId);

        $this->clearCache();

        return $currency;
    }

    private function clearCache(): void
    {
        Cache::store('redis')->forget(self::CACHE_KEY);
    }
}