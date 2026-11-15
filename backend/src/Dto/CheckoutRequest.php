<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** Body of POST /api/checkout: { items: [{ offerId, quantity }], card: { number, expiry, cvc, name } }. */
final readonly class CheckoutRequest
{
    /**
     * @param list<CartItemInput> $items same rules as CartRequest
     */
    public function __construct(
        #[Assert\Count(min: 1, max: 50, minMessage: 'The cart is empty.', maxMessage: 'The cart can hold at most {{ limit }} different items.')]
        #[Assert\Valid]
        public array $items = [],

        #[Assert\Valid]
        public CardInput $card = new CardInput(),
    ) {
    }
}
