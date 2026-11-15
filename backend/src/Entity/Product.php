<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A catalog item, e.g. "ELDEN RING Steam Key GLOBAL". The same game on another
 * platform or region is a separate product (as on Eneba).
 *
 * Search uses a GIN trigram index on lower(name). Doctrine can't describe expression
 * indexes, so it is created by hand in the first migration.
 */
#[ORM\Entity]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** URL-friendly unique name, used in /product/{slug}. */
    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column(length: 500)]
    private string $coverUrl;

    /** e.g. Steam, PSN, Xbox, Nintendo, EA App, Ubisoft Connect, Battle.net */
    #[ORM\Column(length: 50)]
    private string $platform;

    /** e.g. GLOBAL, EUROPE, NORTH AMERICA */
    #[ORM\Column(length: 50)]
    private string $region;

    /** Publisher's list price in EUR cents; discounts are measured against it. */
    #[ORM\Column]
    private int $msrpCents;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $releaseDate;

    /** @var Collection<int, Offer> inverse side of Offer::$product */
    #[ORM\OneToMany(targetEntity: Offer::class, mappedBy: 'product')]
    private Collection $offers;

    public function __construct(
        string $slug,
        string $name,
        string $description,
        string $coverUrl,
        string $platform,
        string $region,
        int $msrpCents,
        \DateTimeImmutable $releaseDate,
    ) {
        $this->slug = $slug;
        $this->name = $name;
        $this->description = $description;
        $this->coverUrl = $coverUrl;
        $this->platform = $platform;
        $this->region = $region;
        $this->msrpCents = $msrpCents;
        $this->releaseDate = $releaseDate;
        $this->offers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCoverUrl(): string
    {
        return $this->coverUrl;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getMsrpCents(): int
    {
        return $this->msrpCents;
    }

    public function getReleaseDate(): \DateTimeImmutable
    {
        return $this->releaseDate;
    }

    /** @return Collection<int, Offer> */
    public function getOffers(): Collection
    {
        return $this->offers;
    }
}
