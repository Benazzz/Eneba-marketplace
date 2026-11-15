<?php

namespace App\Dto;

/** An order with its keys: the response of POST /api/checkout and GET /api/orders/{id} (PLAN.md §4). */
final readonly class OrderView
{
    /**
     * @param list<OrderItemView> $items
     */
    public function __construct(
        public int $id,
        /** ISO 8601 */
        public string $createdAt,
        public int $totalCents,
        public string $cardLast4,
        public array $items,
    ) {
    }
}
