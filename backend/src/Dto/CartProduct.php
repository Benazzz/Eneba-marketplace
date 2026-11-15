<?php

namespace App\Dto;

use App\Entity\Product;

/** The product part of a cart line: what the cart needs to show and link it. */
final readonly class CartProduct
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public string $coverUrl,
        public string $platform,
        public string $region,
    ) {
    }

    public static function fromEntity(Product $product): self
    {
        return new self(
            id: (int) $product->getId(),
            slug: $product->getSlug(),
            name: $product->getName(),
            coverUrl: $product->getCoverUrl(),
            platform: $product->getPlatform(),
            region: $product->getRegion(),
        );
    }
}
