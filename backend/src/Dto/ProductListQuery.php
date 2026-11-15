<?php

namespace App\Dto;

use App\Search\ProductSort;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query string of GET /api/products and GET /api/products/filters (PLAN.md §4), e.g.
 *   ?q=witcher&platform[]=Steam&platform[]=PSN&region[]=GLOBAL&minPrice=1000&maxPrice=3000&sort=price_asc&page=2
 *
 * Bound with #[MapQueryString], like [FromQuery] on a class in ASP.NET Core. Query values are always
 * strings, so the serializer converts "2" to int and "price_asc" to the enum. A wrong type or a broken
 * constraint becomes 422 validation_failed with per-field messages (ApiExceptionSubscriber).
 * platform[]=… is PHP's syntax for a list in a query string; plain repeated keys would keep only the last.
 */
final readonly class ProductListQuery
{
    public const MAX_LIMIT = 48;

    /**
     * @param list<string> $platform OR within the list, AND with the other filters
     * @param list<string> $region   OR within the list, AND with the other filters
     */
    public function __construct(
        #[Assert\Length(max: 100)]
        public string $q = '',
        #[Assert\Count(max: 20)]
        #[Assert\All([new Assert\Type('string'), new Assert\Length(max: 50)])]
        public array $platform = [],
        #[Assert\Count(max: 20)]
        #[Assert\All([new Assert\Type('string'), new Assert\Length(max: 50)])]
        public array $region = [],
        /** Cents, compared with the "From" price (cheapest offer in stock). */
        #[Assert\PositiveOrZero]
        public ?int $minPrice = null,
        #[Assert\PositiveOrZero]
        public ?int $maxPrice = null,
        /** Null = the default: relevance while searching, popular otherwise. */
        public ?ProductSort $sort = null,
        // An upper bound too, so (page - 1) * limit can't overflow into a float for the SQL OFFSET.
        #[Assert\Range(min: 1, max: 10000)]
        public int $page = 1,
        #[Assert\Range(min: 1, max: self::MAX_LIMIT)]
        public int $limit = 24,
    ) {
    }
}
