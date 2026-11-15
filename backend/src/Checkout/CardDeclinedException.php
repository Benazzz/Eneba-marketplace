<?php

namespace App\Checkout;

/** The (simulated) bank refused the payment. CheckoutController turns it into 402 card_declined. */
final class CardDeclinedException extends \RuntimeException
{
    public function __construct()
    {
        // The message never includes card details.
        parent::__construct('Your card was declined. Nothing was charged. Please try another card.');
    }
}
