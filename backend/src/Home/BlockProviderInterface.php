<?php

namespace App\Home;

use App\Dto\BannerSlide;
use App\Dto\ProductCard;
use App\Entity\HomeBlock;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Fills one type of homepage block from its own data source (PLAN.md §2).
 *
 * #[AutoconfigureTag] puts the tag on every class that implements this interface, so a new provider only
 * has to exist in src/ to be picked up. HomeBlocks receives all of them as one iterable, like injecting
 * IEnumerable<IBlockProvider> after registering each implementation in ASP.NET Core DI.
 */
#[AutoconfigureTag(self::TAG)]
interface BlockProviderInterface
{
    public const TAG = 'app.home_block_provider';

    /** Whether this provider fills blocks of this type (e.g. "deals"). */
    public function supports(string $type): bool;

    /**
     * The block's items. May throw: the controller turns a failure into an error for this block only.
     *
     * @return list<BannerSlide>|list<ProductCard>
     */
    public function provide(HomeBlock $block): array;
}
