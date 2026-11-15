<?php

namespace App\Checkout;

use App\Dto\CardInput;

/**
 * Stands in for a payment provider (PLAN.md §7, Decision 7). No real money moves.
 *
 * Like a provider's test mode, it knows fixed test cards: one that always succeeds and one that the "bank"
 * always declines. Any other number is already rejected by CardInput's validation, so nobody is
 * nudged to type a real card into a demo.
 */
final class PaymentSimulator
{
    public const APPROVED_CARD = '4242424242424242';
    public const DECLINED_CARD = '4000000000000002';

    public static function isTestCard(#[\SensitiveParameter] string $number): bool
    {
        return $number === self::APPROVED_CARD || $number === self::DECLINED_CARD;
    }

    /**
     * "Charges" the card and returns its last 4 digits: the only part of the card that is ever stored.
     *
     * @throws CardDeclinedException
     */
    public function charge(#[\SensitiveParameter] CardInput $card, int $amountCents): string
    {
        if ($amountCents <= 0) {
            throw new \InvalidArgumentException('The amount to charge must be positive.');
        }

        if ($card->number === self::DECLINED_CARD) {
            throw new CardDeclinedException();
        }

        return substr($card->number, -4);
    }
}
