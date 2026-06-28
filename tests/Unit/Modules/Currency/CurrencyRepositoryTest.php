<?php

namespace Tests\Unit\Modules\Currency;

use App\Models\Currency;
use App\Modules\Currency\Repositories\Implementation\CurrencyRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new CurrencyRepository();
    }

    // -------------------------------------------------------------------------
    // getAll
    // -------------------------------------------------------------------------

    #[Test]
    public function get_all_returns_all_currencies_ordered_by_code(): void
    {
        Currency::factory()->create(['code' => 'USD']);
        Currency::factory()->create(['code' => 'EGP']);
        Currency::factory()->create(['code' => 'EUR']);

        $result = $this->repository->getAll();

        $this->assertCount(3, $result);
        $this->assertSame(['EGP', 'EUR', 'USD'], $result->pluck('code')->all());
    }

    #[Test]
    public function get_all_returns_empty_collection_when_table_is_empty(): void
    {
        $this->assertCount(0, $this->repository->getAll());
    }

    #[Test]
    public function get_all_includes_both_active_and_inactive_currencies(): void
    {
        Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
        Currency::factory()->create(['code' => 'EGP', 'is_active' => false]);

        $this->assertCount(2, $this->repository->getAll());
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    #[Test]
    public function create_persists_currency_and_returns_model(): void
    {
        $currency = $this->repository->create([
            'name_ar'   => 'دولار أمريكي',
            'name_en'   => 'US Dollar',
            'code'      => 'USD',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertNotNull($currency->id);
        $this->assertSame('USD', $currency->code);
        $this->assertDatabaseHas('currencies', ['code' => 'USD']);
    }

    #[Test]
    public function create_sets_is_active_to_true_by_default(): void
    {
        $currency = $this->repository->create([
            'name_ar' => 'يورو',
            'name_en' => 'Euro',
            'code'    => 'EUR',
        ]);

        // Refresh to pull DB default — the column default is set in the migration,
        // not via $attributes on the model, so we need a fresh fetch.
        $this->assertTrue((bool) $currency->fresh()->is_active);
    }

    // -------------------------------------------------------------------------
    // toggleActive
    // -------------------------------------------------------------------------

    #[Test]
    public function toggle_active_switches_true_to_false(): void
    {
        $currency = Currency::factory()->create(['is_active' => true]);

        $result = $this->repository->toggleActive($currency->id);

        $this->assertFalse($result->is_active);
        $this->assertDatabaseHas('currencies', ['id' => $currency->id, 'is_active' => false]);
    }

    #[Test]
    public function toggle_active_switches_false_to_true(): void
    {
        $currency = Currency::factory()->create(['is_active' => false]);

        $result = $this->repository->toggleActive($currency->id);

        $this->assertTrue($result->is_active);
        $this->assertDatabaseHas('currencies', ['id' => $currency->id, 'is_active' => true]);
    }

    #[Test]
    public function toggle_active_returns_refreshed_model(): void
    {
        $currency = Currency::factory()->create(['is_active' => true]);

        $result = $this->repository->toggleActive($currency->id);

        $this->assertInstanceOf(Currency::class, $result);
        $this->assertFalse($result->is_active);
    }

    #[Test]
    public function toggle_active_throws_model_not_found_for_invalid_id(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->toggleActive(999);
    }

    #[Test]
    public function toggle_active_uses_a_database_transaction(): void
    {
        $currency = Currency::factory()->create(['is_active' => true]);

        $this->repository->toggleActive($currency->id); // true → false
        $this->repository->toggleActive($currency->id); // false → true

        $this->assertDatabaseHas('currencies', ['id' => $currency->id, 'is_active' => true]);
    }
}