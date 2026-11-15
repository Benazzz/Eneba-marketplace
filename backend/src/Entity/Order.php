<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/** A completed purchase. Table is "orders" because ORDER is a reserved SQL word. */
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /** Sum of the item lines, kept up to date by addItem(). EUR cents. */
    #[ORM\Column]
    private int $totalCents = 0;

    /** Only the last 4 card digits are ever stored. */
    #[ORM\Column(length: 4)]
    private string $cardLast4;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * cascade persist: persisting the order also persists its new items.
     *
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist'])]
    private Collection $items;

    public function __construct(User $user, string $cardLast4, ?\DateTimeImmutable $createdAt = null)
    {
        $this->user = $user;
        $this->cardLast4 = $cardLast4;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    /** Adds a line with the offer's current price and product name snapshotted. */
    public function addItem(Offer $offer, int $quantity): OrderItem
    {
        $item = new OrderItem($this, $offer, $quantity);
        $this->items->add($item);
        $this->totalCents += $item->getUnitPriceCents() * $quantity;

        return $item;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTotalCents(): int
    {
        return $this->totalCents;
    }

    public function getCardLast4(): string
    {
        return $this->cardLast4;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }
}
