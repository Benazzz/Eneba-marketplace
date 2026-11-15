<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * One line of an order. Product name and unit price are copied ("snapshotted") at
 * purchase time, so later price or name changes don't rewrite order history.
 * Created through Order::addItem().
 */
#[ORM\Entity]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private Order $order;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Offer $offer;

    #[ORM\Column(length: 255)]
    private string $productName;

    #[ORM\Column]
    private int $unitPriceCents;

    #[ORM\Column]
    private int $quantity;

    /** @var Collection<int, GameKey> the keys delivered by this line (inverse of GameKey::$orderItem) */
    #[ORM\OneToMany(targetEntity: GameKey::class, mappedBy: 'orderItem')]
    private Collection $keys;

    public function __construct(Order $order, Offer $offer, int $quantity)
    {
        $this->order = $order;
        $this->offer = $offer;
        $this->productName = $offer->getProduct()->getName();
        $this->unitPriceCents = $offer->getPriceCents();
        $this->quantity = $quantity;
        $this->keys = new ArrayCollection();
    }

    /** Marks the key as sold to this line and keeps both sides of the relation in sync. */
    public function deliver(GameKey $key, \DateTimeImmutable $soldAt): void
    {
        $key->markSold($this, $soldAt);
        $this->keys->add($key);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getUnitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /** @return Collection<int, GameKey> */
    public function getKeys(): Collection
    {
        return $this->keys;
    }
}
