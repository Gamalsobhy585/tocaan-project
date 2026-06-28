<?php

namespace Tests\Unit\Modules\Currency;

use App\Models\Currency;
use App\Modules\Currency\Repositories\Interfaces\ICurrencyRepository;
use App\Modules\Currency\Services\CurrencyService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyServiceTest extends TestCase
{
    private ICurrencyRepository|MockInterface $repository;
    private CurrencyService $service;

    private const CACHE_KEY = 'master_data:currencies:all';

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(ICurrencyRepository::class);
        $this->service    = new CurrencyService($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    #[Test]
    public function index_fetches_from_repository_on_cache_miss(): void
    {
        Cache::store('redis')->forget(self::CACHE_KEY);

        $expected = new Collection([
            $this->makeCurrency('EGP'),
            $this->makeCurrency('USD'),
        ]);

        $this->repository
            ->shouldReceive('getAll')
            ->once()
            ->andReturn($expected);

        $result = $this->service->index();

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function index_returns_cached_value_without_hitting_repository(): void
    {
        $cached = new Collection([$this->makeCurrency('USD')]);

        Cache::store('redis')->put(self::CACHE_KEY, $cached);

        $this->repository->shouldNotReceive('getAll');

        $result = $this->service->index();

        $this->assertEquals($cached, $result);
    }

    // -------------------------------------------------------------------------
    // add
    // -------------------------------------------------------------------------

    #[Test]
    public function add_delegates_to_repository_and_returns_currency(): void
    {
        $data     = ['name_ar' => 'دولار', 'name_en' => 'Dollar', 'code' => 'USD'];
        $currency = $this->makeCurrency('USD');

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($currency);

        $result = $this->service->add($data);

        $this->assertSame($currency, $result);
    }

    #[Test]
    public function add_clears_cache_after_creation(): void
    {
        Cache::store('redis')->put(self::CACHE_KEY, new Collection());

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->andReturn($this->makeCurrency('USD'));

        $this->service->add(['name_ar' => 'د', 'name_en' => 'D', 'code' => 'USD']);

        $this->assertNull(Cache::store('redis')->get(self::CACHE_KEY));
    }

    // -------------------------------------------------------------------------
    // toggleActive
    // -------------------------------------------------------------------------

    #[Test]
    public function toggle_active_delegates_to_repository_and_returns_currency(): void
    {
        $currency = $this->makeCurrency('USD', false);

        $this->repository
            ->shouldReceive('toggleActive')
            ->once()
            ->with(1)
            ->andReturn($currency);

        $result = $this->service->toggleActive(1);

        $this->assertSame($currency, $result);
    }

    #[Test]
    public function toggle_active_clears_cache(): void
    {
        Cache::store('redis')->put(self::CACHE_KEY, new Collection());

        $this->repository
            ->shouldReceive('toggleActive')
            ->once()
            ->andReturn($this->makeCurrency('USD'));

        $this->service->toggleActive(1);

        $this->assertNull(Cache::store('redis')->get(self::CACHE_KEY));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCurrency(string $code, bool $isActive = true): Currency
    {
        $currency            = new Currency();
        $currency->id        = 1;
        $currency->name_ar   = 'عملة';
        $currency->name_en   = 'Currency';
        $currency->code      = $code;
        $currency->is_active = $isActive;

        return $currency;
    }
}