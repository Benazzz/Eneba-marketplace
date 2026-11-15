<?php

namespace App\Dto;

use App\Entity\HomeBlock;

/** One homepage block with its items (GET /api/home/blocks/{id}). */
final readonly class HomeBlockContent
{
    /**
     * @param list<BannerSlide>|list<ProductCard> $items banners for hero_banner, product cards for the rows
     */
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public array $items,
    ) {
    }

    /** @param list<BannerSlide>|list<ProductCard> $items */
    public static function fromEntity(HomeBlock $block, array $items): self
    {
        return new self((int) $block->getId(), $block->getType(), $block->getTitle(), $items);
    }
}
