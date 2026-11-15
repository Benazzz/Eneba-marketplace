<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** A marketplace merchant that lists offers for products. */
#[ORM\Entity]
class Seller
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    /**
     * 0.00–5.00, stored as numeric(3,2). Doctrine returns DECIMAL as a PHP string
     * (like C# decimal, it avoids float rounding), e.g. "4.87".
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2)]
    private string $rating;

    #[ORM\Column]
    private bool $verified;

    public function __construct(string $name, string $rating, bool $verified)
    {
        $this->name = $name;
        $this->rating = $rating;
        $this->verified = $verified;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRating(): string
    {
        return $this->rating;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }
}
