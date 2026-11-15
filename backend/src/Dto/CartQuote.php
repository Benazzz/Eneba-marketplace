<?php

namespace App\Dto;

/** Response of POST /api/cart/quote. */
final readonly class CartQuote
{
    /**
     * @param list<CartQuoteLine> $lines in the cart's order. Offers that don't exist are left out.
     */
    public function __construct(
        public array $lines,
        /** Sum of every line's lineTotalCents. */
        public int $totalCents,
    ) {
    }
}
