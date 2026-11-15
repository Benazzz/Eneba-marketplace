<?php

namespace App\Dto;

use App\Entity\HomeBlock;

/** One entry of the homepage layout (GET /api/home). The frontend fetches its content separately. */
final readonly class HomeBlockSummary
{
    public function __construct(
        public int $id,
        /** hero_banner | best_sellers | deals | new_releases */
        public string $type,
        public string $title,
    ) {
    }

    public static function fromEntity(HomeBlock $block): self
    {
        return new self((int) $block->getId(), $block->getType(), $block->getTitle());
    }
}
