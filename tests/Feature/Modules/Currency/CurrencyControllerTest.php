<?php

namespace Tests\Feature\Modules\Currency;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CurrencyControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('redis')->flush();

        $user        = User::factory()->create();
        $this->token = JWTAuth::fromUser($user);
    }

    // Sends every request with the JWT Bearer token
    private function authGet(string $url): \Illuminate\Testing\TestResponse
    {
        return $this->getJson($url, ['Authorization' => "Bearer {$this->token}"]);
    }

    private function authPost(string $url, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson($url, $data, ['Authorization' => "Bearer {$this->token}"]);
    }

    private function authPatch(string $url): \Illuminate\Testing\TestResponse
    {
        return $this->patchJson($url, [], ['Authorization' => "Bearer {$this->token}"]);
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_all_currencies_ordered_by_code(): void
    {
        Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
        Currency::factory()->create(['code' => 'EGP', 'is_active' => false]);
        Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);

        $response = $this->authGet(route('currencies.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name_ar', 'name_en', 'code', 'is_active'],
                ],
            ]);

        $codes = collect($response->json('data'))->pluck('code')->values()->all();
        $this->assertSame(['EGP', 'EUR', 'USD'], $codes);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_currencies_exist(): void
    {
        $this->authGet(route('currencies.index'))
            ->assertOk()
            ->assertJson(['data' => []]);
    }

    #[Test]
    public function it_serves_currencies_from_cache_on_second_request(): void
    {
        Currency::factory()->create(['code' => 'USD']);

        $this->authGet(route('currencies.index'))->assertOk();

        // Inserted after cache was primed — must NOT appear
        Currency::factory()->create(['code' => 'ZZZ']);

        $codes = collect(
            $this->authGet(route('currencies.index'))->json('data')
        )->pluck('code')->all();

        $this->assertNotContains('ZZZ', $codes);
    }

    // -------------------------------------------------------------------------
    // add
    // -------------------------------------------------------------------------

    #[Test]
    public function it_creates_a_currency_with_valid_data(): void
    {
        $response = $this->authPost(route('currencies.add'), [
            'name_ar' => 'دولار أمريكي',
            'name_en' => 'US Dollar',
            'code'    => 'USD',
        ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'name_ar'   => 'دولار أمريكي',
                'name_en'   => 'US Dollar',
                'code'      => 'USD',
                'is_active' => true,
            ]);

        $this->assertDatabaseHas('currencies', ['code' => 'USD']);
    }

    #[Test]
    public function it_invalidates_cache_after_adding_a_currency(): void
    {
        $this->authGet(route('currencies.index'))->assertOk();

        $this->authPost(route('currencies.add'), [
            'name_ar' => 'يورو',
            'name_en' => 'Euro',
            'code'    => 'EUR',
        ])->assertCreated();

        $codes = collect(
            $this->authGet(route('currencies.index'))->json('data')
        )->pluck('code')->all();

        $this->assertContains('EUR', $codes);
    }

    #[Test]
    public function it_rejects_a_duplicate_currency_code(): void
    {
        Currency::factory()->create(['code' => 'USD']);

        $this->authPost(route('currencies.add'), [
            'name_ar' => 'دولار',
            'name_en' => 'Dollar',
            'code'    => 'USD',
        ])->assertUnprocessable()
          ->assertJsonValidationErrors(['code']);
    }

    #[Test]
    public function it_rejects_a_code_longer_than_three_characters(): void
    {
        $this->authPost(route('currencies.add'), [
            'name_ar' => 'دولار',
            'name_en' => 'Dollar',
            'code'    => 'USDD',
        ])->assertUnprocessable()
          ->assertJsonValidationErrors(['code']);
    }

    #[Test]
    #[DataProvider('requiredFieldsProvider')]
    public function it_requires_all_mandatory_fields(string $missingField): void
    {
        $payload = [
            'name_ar' => 'دولار أمريكي',
            'name_en' => 'US Dollar',
            'code'    => 'USD',
        ];

        unset($payload[$missingField]);

        $this->authPost(route('currencies.add'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$missingField]);
    }

    public static function requiredFieldsProvider(): array
    {
        return [
            'missing name_ar' => ['name_ar'],
            'missing name_en' => ['name_en'],
            'missing code'    => ['code'],
        ];
    }

    // -------------------------------------------------------------------------
    // toggleActive
    // -------------------------------------------------------------------------

    #[Test]
    public function it_activates_an_inactive_currency(): void
    {
        $currency = Currency::factory()->create(['is_active' => false]);

        $this->authPatch(route('currencies.toggle-active', $currency))
            ->assertOk()
            ->assertJsonFragment(['is_active' => true])
            ->assertJsonFragment(['message' => 'Currency activated successfully.']);

        $this->assertDatabaseHas('currencies', [
            'id'        => $currency->id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_deactivates_an_active_currency(): void
    {
        $currency = Currency::factory()->create(['is_active' => true]);

        $this->authPatch(route('currencies.toggle-active', $currency))
            ->assertOk()
            ->assertJsonFragment(['is_active' => false])
            ->assertJsonFragment(['message' => 'Currency deactivated successfully.']);

        $this->assertDatabaseHas('currencies', [
            'id'        => $currency->id,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function it_invalidates_cache_after_toggling_active(): void
    {
        $currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);

        $this->authGet(route('currencies.index'))->assertOk();

        $this->authPatch(route('currencies.toggle-active', $currency))->assertOk();

        $found = collect(
            $this->authGet(route('currencies.index'))->json('data')
        )->firstWhere('code', 'USD');

        $this->assertFalse($found['is_active']);
    }

    #[Test]
    public function it_returns_404_when_toggling_a_non_existent_currency(): void
    {
        $this->authPatch(route('currencies.toggle-active', 999))
            ->assertNotFound();
    }
}