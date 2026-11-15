<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Plugged into Symfony's built-in access_token authenticator (security.yaml).
 *
 * Symfony reads "Authorization: Bearer <token>" from the request and gives us the token. Our only
 * job is to say which user it belongs to, or reject it. It plays the role of a custom
 * AuthenticationHandler in ASP.NET Core.
 */
final class ApiTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private readonly ApiTokenService $tokens)
    {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $token = $this->tokens->findValid($accessToken);
        if ($token === null) {
            // Unknown, revoked (logged out) or expired. Symfony answers 401 through ApiAuthErrorResponder.
            throw new BadCredentialsException('Invalid or expired token.');
        }

        $user = $token->getUser();

        // The second argument loads the user. The token row already has it, so no extra query is needed.
        return new UserBadge($user->getUserIdentifier(), static fn () => $user);
    }
}
