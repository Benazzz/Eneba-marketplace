<?php

namespace App\Checkout;

use App\Dto\OrderItemView;
use App\Dto\OrderSummary;
use App\Dto\OrderView;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Read queries for a user's orders: the order page (with keys) and the order history.
 * Every query filters by user, so nobody can read someone else's order by guessing its ID.
 */
final class OrderReader
{
    /** The order with its items, their offers and products, in one query (fetch joins, no N+1). */
    private const ORDER_DQL = <<<'DQL'
        SELECT o, i, f, p
        FROM App\Entity\Order o
        JOIN o.items i
        JOIN i.offer f
        JOIN f.product p
        WHERE o.id = :id AND o.user = :user
        ORDER BY i.id
        DQL;

    /**
     * Key codes per order item, as plain values. Reading them from the database (rather than through
     * OrderItem::getKeys()) matters right after checkout: CheckoutService marks keys sold with SQL, so
     * the entities in memory don't know about them.
     */
    private const KEYS_DQL = <<<'DQL'
        SELECT IDENTITY(k.orderItem) AS itemId, k.code
        FROM App\Entity\GameKey k
        JOIN k.orderItem i
        WHERE i.order = :order
        ORDER BY k.id
        DQL;

    private const HISTORY_DQL = <<<'DQL'
        SELECT o, i
        FROM App\Entity\Order o
        JOIN o.items i
        WHERE o.user = :user
        ORDER BY o.createdAt DESC, o.id DESC, i.id
        DQL;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /** Null when the order doesn't exist or belongs to another user (both become 404). */
    public function findForUser(User $user, int $orderId): ?OrderView
    {
        /** @var Order|null $order */
        $order = $this->em->createQuery(self::ORDER_DQL)
            ->setParameter('id', $orderId)
            ->setParameter('user', $user)
            ->getOneOrNullResult();
        if ($order === null) {
            return null;
        }

        $keysByItem = [];
        foreach ($this->em->createQuery(self::KEYS_DQL)->setParameter('order', $order)->getArrayResult() as $row) {
            $keysByItem[(int) $row['itemId']][] = $row['code'];
        }

        $items = [];
        foreach ($order->getItems() as $item) {
            $product = $item->getOffer()->getProduct();
            $items[] = new OrderItemView(
                productName: $item->getProductName(),
                productSlug: $product->getSlug(),
                coverUrl: $product->getCoverUrl(),
                unitPriceCents: $item->getUnitPriceCents(),
                quantity: $item->getQuantity(),
                keys: $keysByItem[(int) $item->getId()] ?? [],
            );
        }

        return new OrderView(
            id: (int) $order->getId(),
            createdAt: $order->getCreatedAt()->format(\DATE_ATOM),
            totalCents: $order->getTotalCents(),
            cardLast4: $order->getCardLast4(),
            items: $items,
        );
    }

    /** @return list<OrderSummary> newest first */
    public function listForUser(User $user): array
    {
        /** @var list<Order> $orders */
        $orders = $this->em->createQuery(self::HISTORY_DQL)->setParameter('user', $user)->getResult();

        return array_map(
            static fn (Order $order) => new OrderSummary(
                id: (int) $order->getId(),
                createdAt: $order->getCreatedAt()->format(\DATE_ATOM),
                totalCents: $order->getTotalCents(),
                items: array_values(array_map(
                    static fn ($item) => ['productName' => $item->getProductName(), 'quantity' => $item->getQuantity()],
                    $order->getItems()->toArray(),
                )),
            ),
            $orders,
        );
    }
}
