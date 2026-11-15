<?php

namespace App\Controller;

use App\Catalog\ProductCatalog;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController
{
    public function __construct(private readonly ProductCatalog $catalog)
    {
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