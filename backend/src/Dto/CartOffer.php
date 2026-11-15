<?php

namespace App\Dto;

/** The offer part of a cart line: price and seller. Stock is the line's `available`. */
final readonly class CartOffer
{
    public function __construct(
        public int $id,
        public int $priceCents,
        /** How far this offer's price is below the product's MSRP, 0–100. */
        public int $discountPercent,
        public SellerSummary $seller,
    ) {
    }
}
