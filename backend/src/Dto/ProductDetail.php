<?php

namespace App\Dto;

/**
 * Everything the product page needs: ProductDetail = ProductCard + { description, releaseDate, offers } (PLAN.md §4).
 *
 * It holds a ProductCard instead of repeating its ten fields. JsonSerializable lets it decide its own
 * JSON (like a custom JsonConverter in .NET): jsonSerialize() spreads the card's fields into the same
 * object, so the API response stays flat.
 */
final readonly class ProductDetail implements \JsonSerializable
{
    /**
     * @param list<ProductOffer> $offers in stock only, cheapest first
     */
    public function __construct(
        public ProductCard $card,
        public string $description,
        /** Y-m-d */
        public string $releaseDate,
        public array $offers,
    ) {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            // get_object_vars() returns the card's public properties as [name => value].
            ...get_object_vars($this->card),
            'description' => $this->description,
            'releaseDate' => $this->releaseDate,
            'offers' => $this->offers,
        ];
    }
}
