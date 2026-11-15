<?php

namespace App\Dto;

/**
 * GET /api/products/filters: the options for the store's filter panel, with faceted counts (PLAN.md §4).
 * Each group's counts apply every other active filter, but not the group's own, so a count says how
 * many results that box would add.
 */
final readonly class ProductFilters
{
    /**
     * @param list<array{value: string, count: int}> $platforms every platform, also those at 0
     * @param list<array{value: string, count: int}> $regions   every region, also those at 0
     * @param array{minCents: ?int, maxCents: ?int}  $price     range of "From" prices, null when nothing matches
     */
    public function __construct(
        public array $platforms,
        public array $regions,
        public array $price,
    ) {
    }
}
