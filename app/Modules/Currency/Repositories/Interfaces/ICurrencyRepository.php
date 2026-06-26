<?php

namespace App\Modules\Currency\Repositories\Interfaces;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

interface ICurrencyRepository
{
    public function getAll(): Collection;

    public function create(array $data): Currency;

    public function toggleActive(int $currencyId): Currency;
}