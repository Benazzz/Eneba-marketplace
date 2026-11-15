<?php

namespace App\Home\Provider;

use App\Dto\BannerSlide;
use App\Entity\Banner;
use App\Entity\HomeBlock;
use App\Home\BlockProviderInterface;
use Doctrine\ORM\EntityManagerInterface;

/** The hero banner. Source: the `banner` table (marketing content, edited separately from the catalog). */
final class HeroBannerProvider implements BlockProviderInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function supports(string $type): bool
    {
        return $type === 'hero_banner';
    }

    /** @return list<BannerSlide> */
    public function provide(HomeBlock $block): array
    {
        // Like EF Core's Banners.Where(b => b.Active).OrderBy(b => b.Position).
        $banners = $this->em->getRepository(Banner::class)->findBy(['active' => true], ['position' => 'ASC']);

        return array_map(BannerSlide::fromEntity(...), $banners);
    }
}
