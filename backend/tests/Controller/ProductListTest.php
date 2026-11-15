<?php

namespace App\Tests\Controller;

use App\Search\ProductSearch;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The store endpoints (PLAN.md §4): GET /api/products and GET /api/products/filters.
 *
 * Read-only requests against the seeded test database (see ProductSearchTest for the setup),
 * sent through the whole app in memory, like WebApplicationFactory in ASP.NET Core.
 */
final class ProductListTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /** /filters must reach its own action, not /api/products/{slug} with slug = "filters" (a 404). */
    public function testFiltersRouteIsNotTakenForAProductSlug(): void
    {
        $data = $this->getJson('/api/products/filters');

        self::assertResponseIsSuccessful();
        self::assertNotEmpty($data['platforms']);
        self::assertNotEmpty($data['regions']);
        self::assertArrayHasKey('minCents', $data['price']);
    }

    /** The store and the header dropdown run the same query, so a search term gives the same list in the same order. */
    public function testSearchInTheStoreMatchesTheDropdown(): void
    {
        foreach (['witcher', 'minecarft', 'gta'] as $term) {
            $store = $this->getJson('/api/products?q='.urlencode($term));
            $dropdown = static::getContainer()->get(ProductSearch::class)->search($term, 24);

            self::assertNotEmpty($dropdown, $term);
            self::assertSame(array_column($dropdown, 'slug'), array_column($store['items'], 'slug'), $term);
        }
    }

    /** A platform filter returns only that platform, and its count in /filters is the number of results. */
    public function testPlatformFilterAgreesWithItsCount(): void
    {
        $list = $this->getJson('/api/products?platform[]=Steam&limit=48');
        self::assertGreaterThan(0, $list['total']);
        self::assertSame(['Steam'], array_values(array_unique(array_column($list['items'], 'platform'))));

        $filters = $this->getJson('/api/products/filters?platform[]=Steam');
        $steam = array_values(array_filter($filters['platforms'], static fn (array $p): bool => $p['value'] === 'Steam'));
        self::assertSame($list['total'], $steam[0]['count']);
    }

    public function testInvalidParametersAreAValidationError(): void
    {
        $data = $this->getJson('/api/products?limit=100&sort=cheapest');

        self::assertResponseStatusCodeSame(422);
        self::assertSame('validation_failed', $data['error']['code']);
    }

    /** @return array<string, mixed> */
    private function getJson(string $url): array
    {
        $this->client->request('GET', $url);

        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }
}
