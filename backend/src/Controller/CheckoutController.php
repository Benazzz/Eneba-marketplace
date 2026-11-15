<?php

namespace App\Controller;

use App\Checkout\CardDeclinedException;
use App\Checkout\CartLines;
use App\Checkout\CheckoutService;
use App\Checkout\OrderReader;
use App\Checkout\OutOfStockException;
use App\Dto\CheckoutRequest;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CheckoutController
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly OrderReader $orders,
    ) {
    }

    /**
     * POST /api/checkout 🔒 { items, card } → 201 Order (with keys) / 402 card_declined / 409 out_of_stock.
     *
     * The two expected failures are answered here as JSON, not thrown on as HTTP exceptions, so they are
     * normal responses and Symfony doesn't log them as errors.
     */
    #[Route('/api/checkout', name: 'api_checkout', methods: ['POST'])]
    public function checkout(#[CurrentUser] User $user, #[MapRequestPayload] CheckoutRequest $body): JsonResponse
    {
        try {
            $orderId = $this->checkout->checkout($user, CartLines::fromItems($body->items), $body->card);
        } catch (CardDeclinedException $e) {
            return new JsonResponse(
                ['error' => ['code' => 'card_declined', 'message' => $e->getMessage()]],
                Response::HTTP_PAYMENT_REQUIRED,
            );
        } catch (OutOfStockException $e) {
            return new JsonResponse(
                ['error' => ['code' => 'out_of_stock', 'message' => $e->getMessage(), 'offerId' => $e->offerId]],
                Response::HTTP_CONFLICT,
            );
        }

        return new JsonResponse($this->orders->findForUser($user, $orderId), Response::HTTP_CREATED);
    }
}
