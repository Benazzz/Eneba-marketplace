<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * One seller's listing of one product, at the seller's price.
 * Stock is not a column: it's the number of available GameKey rows for this offer.
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'offer_product_seller_uniq', columns: ['product_id', 'seller_id'])]
class Offer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'offers')]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Seller $seller;

    /** EUR cents. */
    #[ORM\Column]
    private int $priceCents;

    public function __construct(Product $product, Seller $seller, int $priceCents)
    {
        $this->product = $product;
        $this->seller = $seller;
        $this->priceCents = $priceCents;
        // Keep the in-memory inverse side in sync (Doctrine only persists the owning side).
        $product->getOffers()->add($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getSeller(): Seller
    {
        return $this->seller;
    }

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }
}
