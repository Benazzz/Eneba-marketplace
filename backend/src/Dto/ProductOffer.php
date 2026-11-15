<?php

namespace App\Dto;

/**
 * One seller's offer as shown on the product page (the `Offer` shape in PLAN.md §4).
 * Named ProductOffer so it isn't confused with the App\Entity\Offer entity.
 */
final readonly class ProductOffer
{
    public function __construct(
        public int $id,
        public int $priceCents,
        /** How far this offer's price is below the product's MSRP, 0–100. */
        public int $discountPercent,
        /** Available keys. Always ≥ 1: the product page only lists offers in stock. */
        public int $stock,
        public SellerSummary $seller,
    ) {
    }
}
