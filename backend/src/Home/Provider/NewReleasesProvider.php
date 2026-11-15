<?php

namespace App\Home\Provider;

use App\Dto\ProductCard;
use App\Entity\HomeBlock;
use App\Home\BlockProviderInterface;
use App\Search\ProductSearch;
use App\Search\ProductSort;

/** New releases. Source: the catalog's `product.release_date`, newest first (ProductSort::Newest). */
final class NewReleasesProvider implements BlockProviderInterface
{
    private const LIMIT = 10;

    public function __construct(private readonly ProductSearch $search)
    {
    }

    public function supports(string $type): bool
    {
        return $type === 'new_releases';
    }

    /** @return list<ProductCard> */
    public function provide(HomeBlock $block): array
    {
        return $this->search->ranked(ProductSort::Newest, self::LIMIT);
    }
}
