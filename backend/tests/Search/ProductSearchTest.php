<?php

namespace App\Tests\Search;

use App\Dto\ProductCard;
use App\Search\ProductSearch;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Runs ProductSearch against the real seeded test database (app_test), because the behaviour
 * under test lives in PostgreSQL (pg_trgm), not in PHP. The seed is deterministic, so the
 * catalog is the same on every machine.
 *
 * One-time setup (see README):
 *   php bin/console doctrine:database:create --env=test
 *   php bin/console doctrine:migrations:migrate --env=test -n
 *   php bin/console app:seed --env=test
 *
 * KernelTestCase boots the Symfony app and gives access to its service container,
 * like WebApplicationFactory<Program>.Services in ASP.NET Core integration tests.
 * The test only reads, so nothing needs to be rolled back.
 */
final class ProductSearchTest extends KernelTestCase
{
    private ProductSearch $search;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $productCount = (int) $container->get(Connection::class)->fetchOne('SELECT COUNT(*) FROM product');
        if ($productCount === 0) {
            self::fail('The test database is empty. Run: php bin/console app:seed --env=test');
        }

        $this->search = $container->get(ProductSearch::class);
    }

    /**
     * Typos and partial names. Each one must find the expected game as the FIRST result,
     * so the ranking is checked too, not just that it appears somewhere.
     *
     * @return iterable<string, array{string, string}> query => [query, expected name prefix]
     */
    public static function findsExpectedProduct(): iterable
    {
        // Typos: matched by word similarity (q <% name), so they depend on the threshold.
        yield 'minecarft' => ['minecarft', 'Minecraft Dungeons'];
        yield 'cyberpnk' => ['cyberpnk', 'Cyberpunk 2077'];
        yield 'eldn ring' => ['eldn ring', 'ELDEN RING'];
        yield 'hollow knigt' => ['hollow knigt', 'Hollow Knight'];
        yield 'stardew vally' => ['stardew vally', 'Stardew Valley'];
        yield 'baldurs gate' => ['baldurs gate', "Baldur's Gate 3"];
        yield 'hogwarts legasy' => ['hogwarts legasy', 'Hogwarts Legacy'];
        yield 'assasins creed' => ['assasins creed', "Assassin's Creed"];
        yield 'god of wr' => ['god of wr', 'God of War'];
        yield 'red ded' => ['red ded', 'Red Dead Redemption 2'];
        yield 'spiderman' => ['spiderman', "Marvel's Spider-Man"];

        // Partial names: matched by ILIKE '%q%', independent of the threshold.
        yield 'witcher' => ['witcher', 'The Witcher 3'];
        yield 'gta' => ['gta', 'Grand Theft Auto V'];
        yield 'elden' => ['elden', 'ELDEN RING'];
        yield 'wild hunt' => ['wild hunt', 'The Witcher 3'];

        // Case and extra spaces don't matter.
        yield 'mixed case, spaces' => ['  CyberPunk   2077 ', 'Cyberpunk 2077'];
    }

    #[DataProvider('findsExpectedProduct')]
    public function testFindsExpectedProductFirst(string $query, string $expectedNamePrefix): void
    {
        $results = $this->search->search($query, 8);

        self::assertNotEmpty($results, sprintf('"%s" found nothing.', $query));
        self::assertStringStartsWith(
            $expectedNamePrefix,
            $results[0]->name,
            sprintf('"%s" ranked first: %s', $query, implode(' | ', self::names($results))),
        );
    }

    /**
     * Games the store doesn't sell. Their closest names score 0.40 or less, so they must find
     * nothing. This guards the other side of the trade-off: if the threshold is lowered to 0.4,
     * "halo" starts returning Hades and this test fails.
     *
     * @return iterable<string, array{string}>
     */
    public static function findsNothing(): iterable
    {
        foreach (['halo', 'fifa', 'sims', 'zelda', 'pokemon', 'call of duty'] as $query) {
            yield $query => [$query];
        }
    }

    #[DataProvider('findsNothing')]
    public function testIrrelevantQueryFindsNothing(string $query): void
    {
        $results = $this->search->search($query, 8);

        self::assertSame([], self::names($results), sprintf('"%s" should find nothing.', $query));
    }

    public function testLikeWildcardsInTheQueryAreLiteral(): void
    {
        // Unescaped, "%" would match every name.
        self::assertSame([], $this->search->search('100%', 8));
        self::assertSame([], $this->search->search('%_%', 8));
    }

    public function testRespectsTheLimit(): void
    {
        // Every seeded name contains "Key", so there are far more than 5 matches.
        self::assertCount(5, $this->search->search('key', 5));
    }

    public function testFromPriceIsTheCheapestOfferThatHasStock(): void
    {
        // In the seed, DOOM Eternal's cheapest offer is sold out, so this checks that sold-out
        // offers are skipped, not just that some minimum is returned.
        $card = $this->search->search('doom eternal', 1)[0];

        $offers = static::getContainer()->get(Connection::class)->fetchAllAssociative(
            "SELECT o.price_cents,
                    (SELECT COUNT(*) FROM game_key k WHERE k.offer_id = o.id AND k.status = 'available') AS stock
             FROM offer o WHERE o.product_id = :id",
            ['id' => $card->id],
        );
        $allPrices = array_map(fn (array $o) => (int) $o['price_cents'], $offers);
        $inStockPrices = array_map(
            fn (array $o) => (int) $o['price_cents'],
            array_filter($offers, fn (array $o) => (int) $o['stock'] > 0),
        );

        self::assertLessThan(min($inStockPrices), min($allPrices), 'Precondition: the cheapest offer should be sold out.');
        self::assertSame(min($inStockPrices), $card->fromPriceCents);
        self::assertSame(count($inStockPrices), $card->offerCount);
    }

    /**
     * @param list<ProductCard> $results
     *
     * @return list<string>
     */
    private static function names(array $results): array
    {
        return array_map(fn (ProductCard $card) => $card->name, $results);
    }
}
