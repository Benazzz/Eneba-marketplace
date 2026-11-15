<?php

namespace App\Checkout;

use App\Dto\CartItemInput;

/** Turns the request's cart items into the form the quote and checkout work with. */
final class CartLines
{
    /**
     * offerId => quantity, in the order the items came in. The same offer listed twice is merged
     * (quantities added), so checkout never tries to lock the same offer's keys twice.
     *
     * @param list<CartItemInput> $items
     *
     * @return array<int, int>
     */
    public static function fromItems(array $items): array
    {
        $lines = [];
        foreach ($items as $item) {
            $lines[$item->offerId] = ($lines[$item->offerId] ?? 0) + $item->quantity;
        }

        return $lines;
    }
}
