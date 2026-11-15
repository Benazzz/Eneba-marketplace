<?php

namespace App\Dto;

use App\Entity\Product;

/**
 * The product summary used in lists: search dropdown, store grid, homepage rows (PLAN.md §4).
 *
 * A read-only data object, like a C# record. json_encode() writes its public properties
 * as JSON keys, so the property names are the API's camelCase field names.
 */
final readonly class ProductCard
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public string $coverUrl,
        public string $platform,
        public string $region,
        /** Cheapest offer that still has stock. Null when every offer is sold out. */
        public ?int $fromPriceCents,
        public int $msrpCents,
        /** How far the "From" price is below MSRP, 0–100. Null when there is no price. */
        public ?int $discountPercent,
        /** Number of offers that still have stock. */
        public int $offerCount,
    ) {
    }

    /**
     * Builds a card from a native SQL row with the columns id, slug, name, cover_url, platform,
     * region, msrp_cents, from_price_cents and offer_count.
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $msrpCents = (int) $row['msrp_cents'];
        $fromPriceCents = $row['from_price_cents'] === null ? null : (int) $row['from_price_cents'];

        return new self(
            id: (int) $row['id'],
            slug: $row['slug'],
            name: $row['name'],
            coverUrl: $row['cover_url'],
            platform: $row['platform'],
            region: $row['region'],
            fromPriceCents: $fromPriceCents,
            msrpCents: $msrpCents,
            discountPercent: self::discountPercent($fromPriceCents, $msrpCents),
            offerCount: (int) $row['offer_count'],
        );
    }

    /**
     * Builds a card from a loaded entity, when the caller has already worked out the
     * "From" price and the number of in-stock offers (e.g. ProductCatalog).
     */
    public static function fromProduct(Product $product, ?int $fromPriceCents, int $offerCount): self
    {
        return new self(
            id: (int) $product->getId(),
            slug: $product->getSlug(),
            name: $product->getName(),
            coverUrl: $product->getCoverUrl(),
            platform: $product->getPlatform(),
            region: $product->getRegion(),
            fromPriceCents: $fromPriceCents,
            msrpCents: $product->getMsrpCents(),
            discountPercent: self::discountPercent($fromPriceCents, $product->getMsrpCents()),
            offerCount: $offerCount,
        );
    }

    /**
     * How far a price is below MSRP, 0–100. Null when there is no price.
     * The one place the discount formula lives (cards and offers both use it).
     */
    public static function discountPercent(?int $priceCents, int $msrpCents): ?int
    {
        if ($priceCents === null || $msrpCents <= 0) {
            return null;
        }

        // Some sellers price above MSRP; that's "no discount", not a negative one.
        return max(0, (int) round(($msrpCents - $priceCents) * 100 / $msrpCents));
    }
}
