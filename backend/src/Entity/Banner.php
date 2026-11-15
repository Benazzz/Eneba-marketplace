<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Marketing slide for the homepage hero block (data source of HeroBannerProvider). */
#[ORM\Entity]
class Banner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255)]
    private string $subtitle;

    #[ORM\Column(length: 500)]
    private string $imageUrl;

    /** Frontend path the banner links to, e.g. /product/elden-ring-steam-key-global */
    #[ORM\Column(length: 500)]
    private string $linkUrl;

    #[ORM\Column]
    private int $position;

    #[ORM\Column]
    private bool $active;

    public function __construct(string $title, string $subtitle, string $imageUrl, string $linkUrl, int $position, bool $active = true)
    {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->imageUrl = $imageUrl;
        $this->linkUrl = $linkUrl;
        $this->position = $position;
        $this->active = $active;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function getLinkUrl(): string
    {
        return $this->linkUrl;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
