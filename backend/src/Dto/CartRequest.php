<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Body of POST /api/cart/quote: { items: [{ offerId, quantity }] }.
 *
 * The @param list<CartItemInput> PHPDoc is what tells the serializer to turn each JSON object into a
 * CartItemInput (PHP arrays have no element type, unlike List<T> in C#). #[Assert\Valid] makes the
 * validator check every item's own constraints too, which it wouldn't do for nested objects by default.
 */
final readonly class CartRequest
{
    /**
     * @param list<CartItemInput> $items
     */
    public function __construct(
        #[Assert\Count(min: 1, max: 50, minMessage: 'The cart is empty.', maxMessage: 'The cart can hold at most {{ limit }} different items.')]
        #[Assert\Valid]
        public array $items = [],
    ) {
    }
}
