<?php

namespace App\Dto;

/** One line of an order, with the keys it delivered. Name and price are the snapshots from purchase time. */
final readonly class OrderItemView
{
    /**
     * @param list<string> $keys
     */
    public function __construct(
        public string $productName,
        /** Current product, for the link and the cover image. */
        public string $productSlug,
        public string $coverUrl,
        public int $unitPriceCents,
        public int $quantity,
        public array $keys,
    ) {
    }
}
