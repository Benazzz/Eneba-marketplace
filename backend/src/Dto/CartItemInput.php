<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** One cart line as the browser stores it: which offer and how many keys (PLAN.md §7). */
final readonly class CartItemInput
{
    public function __construct(
        #[Assert\Positive(message: 'Unknown offer.')]
        public int $offerId = 0,

        // 100 is only a sanity limit: no offer has that many keys, so the real limit is the stock.
        #[Assert\Range(notInRangeMessage: 'Quantity must be between {{ min }} and {{ max }}.', min: 1, max: 100)]
        public int $quantity = 0,
    ) {
    }
}
