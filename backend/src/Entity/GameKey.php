<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * One digital key in an offer's stock. Once sold it points to the order item that
 * delivered it, and it can never be sold again.
 */
#[ORM\Entity]
#[ORM\Index(name: 'game_key_offer_status_idx', columns: ['offer_id', 'status'])] // checkout: "available keys of offer X"
class GameKey
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Offer $offer;

    #[ORM\Column(length: 32, unique: true)]
    private string $code;

    #[ORM\Column(length: 16, enumType: KeyStatus::class)]
    private KeyStatus $status = KeyStatus::Available;

    /** Null until sold. */
    #[ORM\ManyToOne(inversedBy: 'keys')]
    private ?OrderItem $orderItem = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $soldAt = null;

    public function __construct(Offer $offer, string $code)
    {
        $this->offer = $offer;
        $this->code = $code;
    }

    /** Called by OrderItem::deliver(), which also adds the key to the item's collection. */
    public function markSold(OrderItem $orderItem, \DateTimeImmutable $soldAt): void
    {
        if ($this->status !== KeyStatus::Available) {
            throw new \LogicException(sprintf('Key %d is already sold.', $this->id));
        }
        $this->status = KeyStatus::Sold;
        $this->orderItem = $orderItem;
        $this->soldAt = $soldAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getStatus(): KeyStatus
    {
        return $this->status;
    }

    public function getOrderItem(): ?OrderItem
    {
        return $this->orderItem;
    }

    public function getSoldAt(): ?\DateTimeImmutable
    {
        return $this->soldAt;
    }
}
