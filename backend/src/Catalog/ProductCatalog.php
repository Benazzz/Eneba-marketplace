<?php

namespace App\Catalog;

use App\Dto\ProductCard;
use App\Dto\ProductDetail;
use App\Dto\ProductOffer;
use App\Dto\SellerSummary;
use App\Entity\KeyStatus;
use App\Entity\Offer;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Read queries for the catalog (product page now, the store list later).
 *
 * Uses Doctrine ORM and DQL (like EF Core and LINQ). Native SQL is kept for Postgres-only
 * features such as pg_trgm search (PLAN.md §2).
 */
final class ProductCatalog
{
    /**
     * In-stock offers of one product, with their seller and stock, cheapest first.
     * The inner JOIN on available keys drops sold-out offers by itself, so the page agrees with
     * ProductCard's "From" price and offerCount, which also only count offers in stock.
     * Selecting `s` too fetch-joins the seller, so there's no extra query per offer (no N+1).
     */
    private const OFFERS_DQL = <<<'DQL'
        SELECT o, s, COUNT(k.id) AS stock
        FROM App\Entity\Offer o
        JOIN o.seller s
        JOIN App\Entity\GameKey k WITH k.offer = o AND k.status = :available
        WHERE o.product = :product
        GROUP BY o, s
        ORDER BY o.priceCents, o.id
        DQL;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /** Null when no product has this slug. */
    public function findBySlug(string $slug): ?ProductDetail
    {
        // Like EF Core's FirstOrDefault(p => p.Slug == slug). slug has a unique index.
        $product = $this->em->getRepository(Product::class)->findOneBy(['slug' => $slug]);
        if ($product === null) {
            return null;
        }

        $offers = $this->findOffersInStock($product);

        return new ProductDetail(
            card: ProductCard::fromProduct($product, $offers[0]->priceCents ?? null, count($offers)),
            description: $product->getDescription(),
            releaseDate: $product->getReleaseDate()->format('Y-m-d'),
            offers: $offers,
        );
    }

    /** @return list<ProductOffer> */
    private function findOffersInStock(Product $product): array
    {
        // A query that selects an entity plus a scalar returns one row per offer:
        // [0 => Offer entity, 'stock' => count].
        $rows = $this->em->createQuery(self::OFFERS_DQL)
            ->setParameter('product', $product)
            ->setParameter('available', KeyStatus::Available)
            ->getResult();

        return array_map(
            static function (array $row) use ($product): ProductOffer {
                /** @var Offer $offer */
                $offer = $row[0];

                return new ProductOffer(
                    id: (int) $offer->getId(),
                    priceCents: $offer->getPriceCents(),
                    discountPercent: (int) ProductCard::discountPercent($offer->getPriceCents(), $product->getMsrpCents()),
                    stock: (int) $row['stock'],
                    seller: SellerSummary::fromEntity($offer->getSeller()),
                );
            },
            $rows,
        );
    }
}
