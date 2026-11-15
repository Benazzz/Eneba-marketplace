<?php

namespace App\Controller;

use App\Checkout\OrderReader;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/** The signed-in user's order history and order pages. Both need sign-in (security.yaml). */
final class OrderController
{
    public function __construct(private readonly OrderReader $orders)
    {
    }

    /** GET /api/orders 🔒 → { items: OrderSummary[] }, newest first */
    #[Route('/api/orders', name: 'api_orders', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        return new JsonResponse(['items' => $this->orders->listForUser($user)]);
    }

    /**
     * GET /api/orders/{id} 🔒 → Order with keys. Someone else's order is a 404, the same as a missing one,
     * so the response doesn't even reveal that the order exists.
     */
    #[Route('/api/orders/{id}', name: 'api_order_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(#[CurrentUser] User $user, int $id): JsonResponse
    {
        $order = $this->orders->findForUser($user, $id);
        if ($order === null) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Order not found.']], 404);
        }

        return new JsonResponse($order);
    }
}
