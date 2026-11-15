<?php

namespace App\Home\Provider;

use App\Dto\ProductCard;
use App\Entity\HomeBlock;
use App\Home\BlockProviderInterface;
use App\Search\ProductSearch;
use App\Search\ProductSort;

/** Top deals. Source: sellers' offer prices vs the product's MSRP, biggest discount first (ProductSort::Discount). */
final class DealsProvider implements BlockProviderInterface
{
    private const LIMIT = 10;

    public function __construct(private readonly ProductSearch $search)
    {
    }

    public function supports(string $type): bool
    {
        return $type === 'deals';
    }

    /** @return list<ProductCard> */
    public function provide(HomeBlock $block): array
    {
        return $this->search->ranked(ProductSort::Discount, self::LIMIT);
    }
}
