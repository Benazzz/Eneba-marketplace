<?php

namespace App\Dto;

/** One row of the order history (GET /api/orders). No keys: those are only on the order's own page. */
final readonly class OrderSummary
{
    /**
     * @param list<array{productName: string, quantity: int}> $items
     */
    public function __construct(
        public int $id,
        /** ISO 8601 */
        public string $createdAt,
        public int $totalCents,
        public array $items,
    ) {
    }
}
