<?php

namespace App\Dto;

/** GET /api/products: one page of the product list (PLAN.md §4). */
final readonly class ProductPage
{
    /**
     * @param list<ProductCard> $items
     */
    public function __construct(
        public array $items,
        /** Matching products on all pages. */
        public int $total,
        public int $page,
        /** Number of pages, 0 when nothing matches. */
        public int $pages,
    ) {
    }
}
