<?php

namespace App\Checkout;

/**
 * A cart line asks for more keys than the offer has free right now. CheckoutController turns it into
 * 409 out_of_stock. The message names the item, so the buyer knows which line to fix.
 */
final class OutOfStockException extends \RuntimeException
{
    public function __construct(
        public readonly int $offerId,
        /** Keys that were free when checkout looked. */
        public readonly int $available,
        string $itemName,
    ) {
        parent::__construct($available === 0
            ? sprintf('Sorry, %s is sold out. Please remove it from your cart.', $itemName)
            : sprintf('Sorry, only %d left of %s. Please lower the quantity in your cart.', $available, $itemName));
    }
}
