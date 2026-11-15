<?php

namespace App\Controller;

use App\Catalog\ProductCatalog;
use App\Dto\ProductListQuery;
use App\Search\ProductSearch;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController
{
    public function __construct(
        private readonly ProductCatalog $catalog,
        private readonly ProductSearch $search,
    ) {
    }

    /**
     * GET /api/products?q=…&platform[]=…&region[]=…&minPrice=…&maxPrice=…&sort=…&page=…&limit=…
     * → { items: ProductCard[], total, page, pages }
     *
     * #[MapQueryString] fills the DTO from the query string and validates it. MapQueryString answers 404
     * by default, so it's set to 422 here: a bad parameter is a client error, not a missing page. With no
     * query string at all, the default value (an empty query = the whole catalog) is used.
     */
    #[Route('/api/products', name: 'api_product_list', methods: ['GET'])]
    public function list(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ProductListQuery $query = new ProductListQuery(),
    ): JsonResponse {
        return new JsonResponse($this->search->find($query));
    }

    /**
     * GET /api/products/filters, same parameters as the list (sort, page and limit are ignored)
     * → { platforms: [{ value, count }], regions: [{ value, count }], price: { minCents, maxCents } }
     *
     * priority: 1 makes the router try this route before /api/products/{slug}, which would otherwise
     * take "filters" as a product slug. (Without a priority, routes are tried in the order they are loaded.)
     */
    #[Route('/api/products/filters', name: 'api_product_filters', methods: ['GET'], priority: 1)]
    public function filters(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ProductListQuery $query = new ProductListQuery(),
    ): JsonResponse {
        return new JsonResponse($this->search->facets($query));
    }

    /** GET /api/products/{slug} → ProductDetail, or 404 not_found */
    #[Route('/api/products/{slug}', name: 'api_product_show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $product = $this->catalog->findBySlug($slug);
        if ($product === null) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Product not found.']], 404);
        }

        return new JsonResponse($product);
    }
}
