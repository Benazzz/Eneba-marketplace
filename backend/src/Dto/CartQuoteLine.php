<?php

namespace App\Dto;

/** One line of a cart quote, priced from the database (PLAN.md §4). */
final readonly class CartQuoteLine
{
    public function __construct(
        public CartOffer $offer,
        public CartProduct $product,
        /** What the cart asked for. Not capped: the cart shows the problem when it's above `available`. */
        public int $quantity,
        /** priceCents × quantity */
        public int $lineTotalCents,
        /** Keys in stock right now. 0 = sold out (the line is kept, so it never silently leaves the cart). */
        public int $available,
    ) {
    }
}
