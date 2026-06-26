<?php

namespace App\Modules\Currency\Services\Interfaces;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

interface ICurrencyService
{
    public function index(): Collection;

    public function add(array $data): Currency;

    public function toggleActive(int $currencyId): Currency;
}