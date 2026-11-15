<?php

namespace App\Dto;

use App\Entity\Banner;

/** One slide of the homepage hero block (PLAN.md §4 `Banner`). */
final readonly class BannerSlide
{
    public function __construct(
        public int $id,
        public string $title,
        public string $subtitle,
        public string $imageUrl,
        /** Frontend path, e.g. /product/elden-ring-steam-key-global */
        public string $linkUrl,
    ) {
    }

    public static function fromEntity(Banner $banner): self
    {
        return new self(
            id: (int) $banner->getId(),
            title: $banner->getTitle(),
            subtitle: $banner->getSubtitle(),
            imageUrl: $banner->getImageUrl(),
            linkUrl: $banner->getLinkUrl(),
        );
    }
}
