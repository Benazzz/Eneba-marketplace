<?php

namespace App\Dto;

use App\Checkout\PaymentSimulator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * The card part of POST /api/checkout. It only lives for the length of the request: nothing stores it,
 * and only PaymentSimulator reads the number (to return its last 4 digits).
 *
 * Keeping the number out of logs:
 * - #[\SensitiveParameter] (PHP 8.2) replaces the argument with a placeholder in stack traces, so an
 *   exception thrown while this object is being built never shows the number or CVC.
 * - No message below uses {{ value }}, so validation errors (which Symfony logs) never repeat the input.
 */
final readonly class CardInput
{
    public function __construct(
        // Digits only: the frontend removes the spaces it shows between groups of four.
        #[Assert\NotBlank(message: 'Enter the card number.')]
        #[Assert\Regex(pattern: '/^\d{12,19}$/', message: 'Enter the card number using digits only.')]
        #[\SensitiveParameter]
        public string $number = '',

        #[Assert\NotBlank(message: 'Enter the expiry date.')]
        #[Assert\Regex(pattern: '#^(0[1-9]|1[0-2])/\d{2}$#', message: 'Use the MM/YY format.')]
        public string $expiry = '',

        #[Assert\NotBlank(message: 'Enter the CVC.')]
        #[Assert\Regex(pattern: '/^\d{3,4}$/', message: 'The CVC is 3 or 4 digits.')]
        #[\SensitiveParameter]
        public string $cvc = '',

        #[Assert\NotBlank(message: 'Enter the name on the card.')]
        #[Assert\Length(max: 100, maxMessage: 'The name must be at most {{ limit }} characters.')]
        public string $name = '',
    ) {
    }

    /**
     * Checks that need code, not just a regex. Each check skips a value whose format is already wrong,
     * since the format message is the useful one then.
     */
    #[Assert\Callback]
    public function validateCard(ExecutionContextInterface $context): void
    {
        // A demo shop must not encourage typing a real card, so only the documented test cards work.
        // (That also makes a checksum (Luhn) check pointless: both test numbers pass it.)
        if (preg_match('/^\d{12,19}$/', $this->number) === 1 && !PaymentSimulator::isTestCard($this->number)) {
            $context->buildViolation('This is a demo shop. Use test card 4242 4242 4242 4242.')
                ->atPath('number')
                ->addViolation();
        }

        if (preg_match('#^(0[1-9]|1[0-2])/(\d{2})$#', $this->expiry, $m) === 1) {
            // A card is valid until the end of its expiry month. "12/30" means December 2030.
            $endOfMonth = new \DateTimeImmutable(sprintf('20%s-%s-01 00:00:00', $m[2], $m[1]));
            if ($endOfMonth->modify('+1 month') <= new \DateTimeImmutable()) {
                $context->buildViolation('This card has expired.')
                    ->atPath('expiry')
                    ->addViolation();
            }
        }
    }
}
