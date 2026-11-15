<?php

namespace App\Search;

use App\Dto\ProductCard;
use App\Dto\ProductFilters;
use App\Dto\ProductListQuery;
use App\Dto\ProductPage;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Every product-list query: the header dropdown (search), the store grid (find) and the store's
 * filter counts (facets). They share one FROM and one WHERE builder, so the dropdown and the store
 * always find the same products for the same search term (PLAN.md §5).
 *
 * Search by name uses PostgreSQL's pg_trgm extension and the GIN trigram index on lower(name).
 * A product matches when either:
 *  - its name contains the query (ILIKE '%q%'): partial names such as "witcher" or "gta", or
 *  - the query is similar to some part of its name (q <% name, word similarity): typos such as
 *    "minecarft" or "cyberpnk". Word similarity compares the query's trigrams (3-letter chunks)
 *    with the best-matching stretch of the name, so the rest of a long title doesn't lower the score.
 *
 * Native SQL through DBAL (like Dapper next to EF Core), because Doctrine's query language
 * doesn't know these Postgres operators.
 */
final class ProductSearch
{
    /**
     * Minimum word similarity (0–1) for a typo match. Postgres's default is 0.6.
     * Measured on the seeded catalog:
     *  - 0.6 loses "minecarft" → Minecraft Dungeons (scores 0.50).
     *  - 0.5 keeps every typo in ProductSearchTest. Known cost: "mario" → Marvel's Spider-Man
     *    also scores exactly 0.50, so no threshold can keep the first and drop the second.
     *  - 0.4 would add "wticher" → Witcher (0.40), but also let in irrelevant results for games we
     *    don't sell: "halo" → Hades, "fifa" → Split Fiction, "sims" → Sid Meier's (all 0.40).
     *  - 0.3 adds noise to real queries: "eldn ring" also returns Skyrim, "god of wr" also
     *    returns Ghost of Tsushima.
     * ProductSearchTest checks both sides: the typo list must match, the "not sold here" list must not.
     */
    public const WORD_SIMILARITY_THRESHOLD = 0.5;

    /** The columns ProductCard::fromRow() reads. */
    private const CARD_COLUMNS = <<<'SQL'
        p.id, p.slug, p.name, p.cover_url, p.platform, p.region, p.msrp_cents,
        stock.from_price_cents, stock.offer_count
        SQL;

    /** One row per product, with its stock-based price and its sales. Shared by every query here. */
    private const FROM = <<<'SQL'
        FROM product p
        -- "From" price: the cheapest offer that still has at least one available key.
        LEFT JOIN LATERAL (
            SELECT MIN(o.price_cents) AS from_price_cents, COUNT(*) AS offer_count
            FROM offer o
            WHERE o.product_id = p.id
              AND EXISTS (SELECT 1 FROM game_key k WHERE k.offer_id = o.id AND k.status = 'available')
        ) stock ON true
        -- Popularity: keys sold.
        LEFT JOIN (
            SELECT o.product_id, SUM(oi.quantity) AS sold
            FROM order_item oi
            JOIN offer o ON o.id = oi.offer_id
            GROUP BY o.product_id
        ) sales ON sales.product_id = p.id
        SQL;

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * The header dropdown: the best matches for a search term.
     *
     * @return list<ProductCard> best match first
     */
    public function search(string $query, int $limit): array
    {
        // An empty term would match the whole catalog; the dropdown wants nothing then.
        if (self::normalize($query) === '') {
            return [];
        }

        $listQuery = new ProductListQuery(q: $query, sort: ProductSort::Relevance, limit: $limit);

        return $this->withThreshold(fn (Connection $conn): array => $this->fetchItems($conn, $listQuery));
    }

    /** The store grid: one page of products matching the search term and filters, in the chosen order. */
    public function find(ProductListQuery $query): ProductPage
    {
        return $this->withThreshold(function (Connection $conn) use ($query): ProductPage {
            [$where, $params, $types] = $this->where($query);
            $total = (int) $conn->fetchOne('SELECT COUNT(*) '.self::FROM.' WHERE '.$where, $params, $types);

            // A page past the end just comes back empty, with the right total, so the client can recover.
            $items = $this->fetchItems($conn, $query);

            return new ProductPage($items, $total, $query->page, (int) ceil($total / $query->limit));
        });
    }

    /**
     * The store's filter panel. Each group is counted with every active filter except its own
     * (faceted search), so ticking a box shows how many results it adds, and the price range isn't
     * narrowed by the price the user just typed.
     */
    public function facets(ProductListQuery $query): ProductFilters
    {
        return $this->withThreshold(function (Connection $conn) use ($query): ProductFilters {
            [$where, $params, $types] = $this->where($query, skip: 'price');
            $price = $conn->fetchAssociative(
                'SELECT MIN(stock.from_price_cents) AS min_cents, MAX(stock.from_price_cents) AS max_cents '.self::FROM.' WHERE '.$where,
                $params,
                $types,
            );

            return new ProductFilters(
                platforms: $this->facetCounts($conn, 'p.platform', $query, skip: 'platform'),
                regions: $this->facetCounts($conn, 'p.region', $query, skip: 'region'),
                price: [
                    'minCents' => $price['min_cents'] === null ? null : (int) $price['min_cents'],
                    'maxCents' => $price['max_cents'] === null ? null : (int) $price['max_cents'],
                ],
            );
        });
    }

    /** @return list<ProductCard> */
    private function fetchItems(Connection $conn, ProductListQuery $query): array
    {
        [$where, $params, $types] = $this->where($query);

        $rows = $conn->fetchAllAssociative(
            'SELECT '.self::CARD_COLUMNS.' '.self::FROM
                .' WHERE '.$where
                .' ORDER BY '.self::sortFor($query)->orderBy()
                .' LIMIT :limit OFFSET :offset',
            $params + ['limit' => $query->limit, 'offset' => ($query->page - 1) * $query->limit],
            $types + ['limit' => ParameterType::INTEGER, 'offset' => ParameterType::INTEGER],
        );

        return array_map(ProductCard::fromRow(...), $rows);
    }

    /**
     * Every value of one column (platform or region), each with the number of products that match
     * the other filters. COUNT(*) FILTER (WHERE …) counts only the matching rows but still returns a
     * row for every value, so a ticked box never disappears just because its count dropped to 0.
     * Ordered by catalog size, not by the filtered count, so the list doesn't jump around while filtering.
     *
     * @param 'p.platform'|'p.region' $column a fixed column name, never user input
     *
     * @return list<array{value: string, count: int}>
     */
    private function facetCounts(Connection $conn, string $column, ProductListQuery $query, string $skip): array
    {
        [$where, $params, $types] = $this->where($query, $skip);

        $rows = $conn->fetchAllAssociative(
            "SELECT $column AS value, COUNT(*) FILTER (WHERE $where) AS matches "
                .self::FROM
                ." GROUP BY $column ORDER BY COUNT(*) DESC, $column",
            $params,
            $types,
        );

        return array_map(
            static fn (array $row): array => ['value' => $row['value'], 'count' => (int) $row['matches']],
            $rows,
        );
    }

    /**
     * Builds the WHERE clause for a query: the search term and the filters, joined with AND.
     * Values only ever go in as bound parameters.
     *
     * @param 'platform'|'region'|'price'|null $skip one filter group to leave out (for facets)
     *
     * @return array{string, array<string, mixed>, array<string, ArrayParameterType>}
     */
    private function where(ProductListQuery $query, ?string $skip = null): array
    {
        $conditions = [];
        $params = [];
        $types = [];

        $q = self::normalize($query->q);
        if ($q !== '') {
            // LIKE uses backslash as its escape character by default (see escapeLike()).
            $conditions[] = '(lower(p.name) LIKE :contains OR :q <% lower(p.name))';
            $params['q'] = $q;
            $params['contains'] = '%'.self::escapeLike($q).'%';
        }

        if ($query->platform !== [] && $skip !== 'platform') {
            // An array parameter expands to IN (?, ?, …), like EF Core's list.Contains(x).
            $conditions[] = 'p.platform IN (:platforms)';
            $params['platforms'] = $query->platform;
            $types['platforms'] = ArrayParameterType::STRING;
        }

        if ($query->region !== [] && $skip !== 'region') {
            $conditions[] = 'p.region IN (:regions)';
            $params['regions'] = $query->region;
            $types['regions'] = ArrayParameterType::STRING;
        }

        // Price filters compare the "From" price. Sold-out products have none, so they drop out.
        if ($skip !== 'price') {
            if ($query->minPrice !== null) {
                $conditions[] = 'stock.from_price_cents >= :minPrice';
                $params['minPrice'] = $query->minPrice;
            }
            if ($query->maxPrice !== null) {
                $conditions[] = 'stock.from_price_cents <= :maxPrice';
                $params['maxPrice'] = $query->maxPrice;
            }
        }

        return [$conditions === [] ? 'TRUE' : implode(' AND ', $conditions), $params, $types];
    }

    /** The chosen sort, or the default: best match while searching, most popular otherwise. */
    private static function sortFor(ProductListQuery $query): ProductSort
    {
        $hasTerm = self::normalize($query->q) !== '';
        $sort = $query->sort ?? ($hasTerm ? ProductSort::Relevance : ProductSort::Popular);

        // Relevance needs a search term to rank by.
        return $sort === ProductSort::Relevance && !$hasTerm ? ProductSort::Popular : $sort;
    }

    /**
     * Runs $work in a transaction with the word-similarity threshold set.
     * The <% operator reads its threshold from a setting, not from an argument. set_config(..., true)
     * is SET LOCAL: it only lasts until the end of the transaction, so the queries must run in the same
     * transaction. That also keeps them on the same server connection behind Neon's pooler.
     *
     * @template T
     *
     * @param callable(Connection): T $work
     *
     * @return T
     */
    private function withThreshold(callable $work): mixed
    {
        return $this->connection->transactional(function (Connection $conn) use ($work): mixed {
            $conn->fetchOne(
                "SELECT set_config('pg_trgm.word_similarity_threshold', :threshold, true)",
                ['threshold' => (string) self::WORD_SIMILARITY_THRESHOLD],
            );

            return $work($conn);
        });
    }

    /** "  Elden   RING " → "elden ring" */
    private static function normalize(string $query): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $query) ?? ''));
    }

    /** Makes %, _ and \ literal in a LIKE pattern, so searching "100%" doesn't match every name. */
    private static function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
