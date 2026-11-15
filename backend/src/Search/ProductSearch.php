<?php

namespace App\Search;

use App\Dto\ProductCard;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Product search by name with typo tolerance (PLAN.md §5). One SQL query, using PostgreSQL's
 * pg_trgm extension and the GIN trigram index on lower(name).
 *
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

    private const SQL = <<<'SQL'
        SELECT p.id, p.slug, p.name, p.cover_url, p.platform, p.region, p.msrp_cents,
               stock.from_price_cents, stock.offer_count
        FROM product p
        -- "From" price: the cheapest offer that still has at least one available key.
        LEFT JOIN LATERAL (
            SELECT MIN(o.price_cents) AS from_price_cents, COUNT(*) AS offer_count
            FROM offer o
            WHERE o.product_id = p.id
              AND EXISTS (SELECT 1 FROM game_key k WHERE k.offer_id = o.id AND k.status = 'available')
        ) stock ON true
        -- Popularity: keys sold, used as a tie-breaker.
        LEFT JOIN (
            SELECT o.product_id, SUM(oi.quantity) AS sold
            FROM order_item oi
            JOIN offer o ON o.id = oi.offer_id
            GROUP BY o.product_id
        ) sales ON sales.product_id = p.id
        -- LIKE uses backslash as its escape character by default (see escapeLike()).
        WHERE lower(p.name) LIKE :contains
           OR :q <% lower(p.name)
        ORDER BY (lower(p.name) LIKE :prefix) DESC,      -- names that start with the query first
                 word_similarity(:q, lower(p.name)) DESC, -- then the closest match
                 COALESCE(sales.sold, 0) DESC,            -- then best sellers
                 p.name
        LIMIT :limit
        SQL;

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return list<ProductCard> best match first
     */
    public function search(string $query, int $limit): array
    {
        $q = self::normalize($query);
        if ($q === '') {
            return [];
        }
        $like = self::escapeLike($q);

        // The <% operator reads its threshold from a setting, not from an argument. set_config(..., true)
        // is SET LOCAL: it only lasts until the end of the transaction, so both statements must run in
        // the same transaction. That also keeps them on the same server connection behind Neon's pooler.
        $rows = $this->connection->transactional(function (Connection $conn) use ($q, $like, $limit): array {
            $conn->fetchOne(
                "SELECT set_config('pg_trgm.word_similarity_threshold', :threshold, true)",
                ['threshold' => (string) self::WORD_SIMILARITY_THRESHOLD],
            );

            return $conn->fetchAllAssociative(
                self::SQL,
                ['q' => $q, 'contains' => '%'.$like.'%', 'prefix' => $like.'%', 'limit' => $limit],
                ['limit' => ParameterType::INTEGER],
            );
        });

        return array_map(ProductCard::fromRow(...), $rows);
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
