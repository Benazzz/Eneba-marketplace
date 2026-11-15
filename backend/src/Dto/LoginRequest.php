<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Body of POST /api/auth/login. Only "required" is checked here. A badly formed email is simply
 * a wrong login, so it gets the same generic message as any other wrong email or password.
 */
final readonly class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Enter your email address.')]
        public string $email = '',

        #[Assert\NotBlank(message: 'Enter your password.')]
        public string $password = '',
    ) {
    }
}
