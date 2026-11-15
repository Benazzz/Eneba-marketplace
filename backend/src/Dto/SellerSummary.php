<?php

namespace App\Dto;

use App\Entity\Seller;

/** The seller part of an offer on the product page (PLAN.md §4). */
final readonly class SellerSummary
{
    public function __construct(
        public int $id,
        public string $name,
        /** 0.00–5.00 */
        public float $rating,
        public bool $verified,
    ) {
    }

    public static function fromEntity(Seller $seller): self
    {
        return new self(
            id: (int) $seller->getId(),
            name: $seller->getName(),
            // Doctrine gives DECIMAL as a string ("4.87"). JSON wants a number; two decimals fit a float exactly enough.
            rating: (float) $seller->getRating(),
            verified: $seller->isVerified(),
        );
    }
}
