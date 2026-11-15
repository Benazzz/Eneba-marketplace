<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Body of POST /api/auth/register. Filled from JSON by #[MapRequestPayload] and then validated,
 * like [FromBody] + DataAnnotations in ASP.NET. Validation errors become 422 with per-field messages
 * (see ApiExceptionSubscriber).
 *
 * The '' defaults make a missing field fail NotBlank with a readable message, instead of a type error.
 */
final readonly class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Enter your email address.')]
        #[Assert\Email(message: 'Enter a valid email address.')]
        // Same limit as the users.email column.
        #[Assert\Length(max: 180, maxMessage: 'Email must be at most {{ limit }} characters.')]
        public string $email = '',

        #[Assert\NotBlank(message: 'Enter a password.')]
        // 4096 is the password hasher's own maximum: longer input would be rejected while hashing.
        #[Assert\Length(
            min: 8,
            max: 4096,
            minMessage: 'Password must be at least {{ limit }} characters.',
            maxMessage: 'Password must be at most {{ limit }} characters.',
        )]
        public string $password = '',
    ) {
    }
}
