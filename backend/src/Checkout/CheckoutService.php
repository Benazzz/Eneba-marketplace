<?php

namespace App\Checkout;

use App\Dto\CardInput;
use App\Entity\KeyStatus;
use App\Entity\Offer;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Places an order: takes free keys, charges the card, and records the order, all in ONE database
 * transaction (PLAN.md §7). Either everything is saved or nothing is.
 *
 * Why a key can never be sold twice:
 * 1. `SELECT … FOR UPDATE SKIP LOCKED` locks the chosen key rows until the transaction ends. A second checkout
 *    running at the same moment can't lock the same rows, and SKIP LOCKED makes it take the *next* free keys
 *    instead of waiting. (Nobody waits for a lock, so two checkouts can't deadlock either.)
 * 2. The UPDATE that marks the keys sold only touches rows that are still 'available', and the number of
 *    changed rows must equal the number of keys. If it doesn't, something went wrong and everything is rolled back.
 *
 * The transaction is opened on the DBAL connection (the layer under Doctrine ORM, like ADO.NET under EF Core)
 * rather than with $em->wrapInTransaction(), because that one also *closes the EntityManager* on any
 * exception, and a declined card or missing stock is an expected outcome here, not a crash.
 */
final class CheckoutService
{
    private const OFFERS_DQL = <<<'DQL'
        SELECT o, p, s
        FROM App\Entity\Offer o
        JOIN o.product p
        JOIN o.seller s
        WHERE o.id IN (:ids)
        DQL;

    /** Native SQL: Doctrine's query languages have no FOR UPDATE SKIP LOCKED. */
    private const LOCK_KEYS_SQL = <<<'SQL'
        SELECT id FROM game_key
        WHERE offer_id = :offer AND status = :available
        ORDER BY id
        LIMIT :quantity
        FOR UPDATE SKIP LOCKED
        SQL;

    private const MARK_SOLD_SQL = <<<'SQL'
        UPDATE game_key
        SET status = :sold, order_item_id = :item, sold_at = :soldAt
        WHERE id IN (:ids) AND status = :available
        SQL;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PaymentSimulator $payments,
    ) {
    }

    /**
     * @param array<int, int> $lines offerId => quantity (see CartLines)
     *
     * @return int the new order's ID
     *
     * @throws OutOfStockException  a line asks for more keys than are free (nothing is saved)
     * @throws CardDeclinedException the card was declined (nothing is saved)
     */
    public function checkout(User $user, array $lines, #[\SensitiveParameter] CardInput $card): int
    {
        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        try {
            // 1. Reload the offers (and their current prices) from the database. The browser's prices are never used.
            $offers = $this->loadOffers(array_keys($lines));

            // 2. Lock free keys for every line. Fewer free keys than asked for = out of stock.
            $keyIdsByOffer = [];
            foreach ($lines as $offerId => $quantity) {
                $offer = $offers[$offerId] ?? null;
                if ($offer === null) {
                    throw new OutOfStockException($offerId, 0, 'an item in your cart');
                }

                $keyIds = $connection->fetchFirstColumn(
                    self::LOCK_KEYS_SQL,
                    ['offer' => $offerId, 'available' => KeyStatus::Available->value, 'quantity' => $quantity],
                    ['quantity' => ParameterType::INTEGER],
                );
                if (count($keyIds) < $quantity) {
                    throw new OutOfStockException($offerId, count($keyIds), self::itemName($offer));
                }
                $keyIdsByOffer[$offerId] = array_map('intval', $keyIds);
            }

            // 3. Charge the card, for the total worked out from database prices. A decline throws, and the
            //    rollback below releases the locked keys. (Fine for an instant simulated charge; see Decision 25
            //    for how a real, slow payment provider would be handled.)
            $totalCents = 0;
            foreach ($lines as $offerId => $quantity) {
                $totalCents += $offers[$offerId]->getPriceCents() * $quantity;
            }
            $cardLast4 = $this->payments->charge($card, $totalCents);

            // 4. Create the order. addItem() snapshots each product name and price. flush() INSERTs the rows and
            //    gives the items their IDs, which step 5 needs. (Still inside the transaction: nothing is final yet.)
            $now = new \DateTimeImmutable();
            $order = new Order($user, $cardLast4, $now);
            $items = [];
            foreach ($lines as $offerId => $quantity) {
                $items[$offerId] = $order->addItem($offers[$offerId], $quantity);
            }
            $this->em->persist($order); // cascades to the items
            $this->em->flush();

            // 5. Hand the locked keys to their order items. Only rows that are still available are changed,
            //    and the count must match exactly (the second safety net).
            foreach ($items as $offerId => $item) {
                $keyIds = $keyIdsByOffer[$offerId];
                $updated = $connection->executeStatement(
                    self::MARK_SOLD_SQL,
                    [
                        'sold' => KeyStatus::Sold->value,
                        'item' => $item->getId(),
                        'soldAt' => $now,
                        'ids' => $keyIds,
                        'available' => KeyStatus::Available->value,
                    ],
                    ['soldAt' => Types::DATETIME_IMMUTABLE, 'ids' => ArrayParameterType::INTEGER],
                );
                if ($updated !== count($keyIds)) {
                    throw new \LogicException(sprintf('Expected to sell %d keys of offer %d, but %d were updated.', count($keyIds), $offerId, $updated));
                }
            }

            // 6. Done: make it all permanent, and release the locks.
            $connection->commit();

            return (int) $order->getId();
        } catch (\Throwable $e) {
            // Undo everything from this checkout: no order, no items, no sold keys.
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * @param list<int> $ids
     *
     * @return array<int, Offer> offerId => offer, with product and seller loaded
     */
    private function loadOffers(array $ids): array
    {
        $offers = [];
        foreach ($this->em->createQuery(self::OFFERS_DQL)->setParameter('ids', $ids)->getResult() as $offer) {
            $offers[(int) $offer->getId()] = $offer;
        }

        return $offers;
    }

    /** e.g. "ELDEN RING Steam Key GLOBAL" from KeyHaven */
    private static function itemName(Offer $offer): string
    {
        return sprintf('"%s" from %s', $offer->getProduct()->getName(), $offer->getSeller()->getName());
    }
}
