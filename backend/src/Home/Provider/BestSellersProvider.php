<?php

namespace App\Home\Provider;

use App\Dto\ProductCard;
use App\Entity\HomeBlock;
use App\Home\BlockProviderInterface;
use App\Search\ProductSearch;
use App\Search\ProductSort;

/** Best sellers. Source: sales, i.e. keys sold in `order_item` (ProductSort::Popular). */
final class BestSellersProvider implements BlockProviderInterface
{
    private const LIMIT = 10;

    public function __construct(private readonly ProductSearch $search)
    {
    }

    public function supports(string $type): bool
    {
        return $type === 'best_sellers';
    }

    /** @return list<ProductCard> */
    public function provide(HomeBlock $block): array
    {
        return $this->search->ranked(ProductSort::Popular, self::LIMIT);
    }
}
