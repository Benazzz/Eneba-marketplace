<?php

namespace App\Controller;

use App\Search\ProductSearch;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Live search for the header dropdown. The store page's ?q= filter uses the same ProductSearch,
 * so both always agree (PLAN.md §5).
 */
final class SearchController
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 100;
    private const SUGGEST_LIMIT = 8;

    public function __construct(private readonly ProductSearch $search)
    {
    }

    /** GET /api/search/suggest?q=… → { items: ProductCard[] } */
    #[Route('/api/search/suggest', name: 'api_search_suggest', methods: ['GET'])]
    public function suggest(#[MapQueryParameter] string $q = ''): JsonResponse
    {
        // #[MapQueryParameter] binds ?q= to the argument, like [FromQuery] in ASP.NET Core.
        $q = trim($q);

        // Too short to be useful: an empty list, not an error (the frontend doesn't send these anyway).
        if (mb_strlen($q) < self::MIN_LENGTH) {
            return new JsonResponse(['items' => []]);
        }

        $items = $this->search->search(mb_substr($q, 0, self::MAX_LENGTH), self::SUGGEST_LIMIT);

        return new JsonResponse(['items' => $items]);
    }
}
