<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * One block of the homepage layout. The type picks the provider that fills it
 * (hero_banner, best_sellers, deals, new_releases); the frontend renders blocks by position.
 */
#[ORM\Entity]
class HomeBlock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column]
    private int $position;

    #[ORM\Column]
    private bool $enabled;

    public function __construct(string $type, string $title, int $position, bool $enabled = true)
    {
        $this->type = $type;
        $this->title = $title;
        $this->position = $position;
        $this->enabled = $enabled;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
