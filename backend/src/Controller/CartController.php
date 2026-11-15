<?php

namespace App\Controller;

use App\Checkout\CartLines;
use App\Checkout\CartQuoter;
use App\Dto\CartRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CartController
{
    public function __construct(private readonly CartQuoter $quoter)
    {
    }

    /**
     * POST /api/cart/quote { items: [{ offerId, quantity }] } → { lines, totalCents }.
     * Public, so guests can see real prices and stock in their cart.
     */
    #[Route('/api/cart/quote', name: 'api_cart_quote', methods: ['POST'])]
    public function quote(#[MapRequestPayload] CartRequest $body): JsonResponse
    {
        return new JsonResponse($this->quoter->quote(CartLines::fromItems($body->items)));
    }
}
