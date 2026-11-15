<?php

namespace App\Checkout;

use App\Dto\CartOffer;
use App\Dto\CartProduct;
use App\Dto\CartQuote;
use App\Dto\CartQuoteLine;
use App\Dto\ProductCard;
use App\Dto\SellerSummary;
use App\Entity\KeyStatus;
use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Prices a cart from the database (POST /api/cart/quote). The browser only sends offer IDs and quantities,
 * so prices and stock shown in the cart always come from here (PLAN.md §7).
 *
 * The cart reserves nothing: an offer can sell out while it sits in a cart. So this is only a preview.
 * CheckoutService checks the stock again, under a lock, when the order is placed.
 */
final class CartQuoter
{
    /**
     * Same idea as ProductCatalog::OFFERS_DQL, but with a LEFT JOIN on the available keys: a sold-out offer
     * still comes back, with available = 0, so it can be shown as "Sold out" instead of vanishing from the cart.
     * Selecting p and s too fetch-joins product and seller, so there's no extra query per line.
     */
    private const QUOTE_DQL = <<<'DQL'
        SELECT o, p, s, COUNT(k.id) AS available
        FROM App\Entity\Offer o
        JOIN o.product p
        JOIN o.seller s
        LEFT JOIN App\Entity\GameKey k WITH k.offer = o AND k.status = :available
        WHERE o.id IN (:ids)
        GROUP BY o, p, s
        DQL;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @param array<int, int> $lines offerId => quantity (see CartLines)
     */
    public function quote(array $lines): CartQuote
    {
        $rows = $this->em->createQuery(self::QUOTE_DQL)
            ->setParameter('ids', array_keys($lines))
            ->setParameter('available', KeyStatus::Available)
            ->getResult();

        // Index the rows by offer ID, so the lines can follow the cart's order below.
        $byOfferId = [];
        foreach ($rows as $row) {
            /** @var Offer $offer */
            $offer = $row[0];
            $byOfferId[(int) $offer->getId()] = ['offer' => $offer, 'available' => (int) $row['available']];
        }

        $quoteLines = [];
        $totalCents = 0;
        foreach ($lines as $offerId => $quantity) {
            if (!isset($byOfferId[$offerId])) {
                continue; // no such offer: left out, and the frontend drops it from the cart
            }
            ['offer' => $offer, 'available' => $available] = $byOfferId[$offerId];
            $product = $offer->getProduct();

            $lineTotalCents = $offer->getPriceCents() * $quantity;
            $totalCents += $lineTotalCents;

            $quoteLines[] = new CartQuoteLine(
                offer: new CartOffer(
                    id: $offerId,
                    priceCents: $offer->getPriceCents(),
                    discountPercent: (int) ProductCard::discountPercent($offer->getPriceCents(), $product->getMsrpCents()),
                    seller: SellerSummary::fromEntity($offer->getSeller()),
                ),
                product: CartProduct::fromEntity($product),
                quantity: $quantity,
                lineTotalCents: $lineTotalCents,
                available: $available,
            );
        }

        return new CartQuote($quoteLines, $totalCents);
    }
}
