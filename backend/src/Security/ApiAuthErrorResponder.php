<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * The 401 response in the API's error format, for both ways a protected request can fail:
 * - no token at all (entry point: Symfony asks "how should this guest start logging in?")
 * - a token that is unknown, revoked or expired (failure handler)
 *
 * Without this class Symfony would send a 401 with an empty body. Both cases look the same to the
 * client on purpose: either way, the user has to log in again.
 */
final class ApiAuthErrorResponder implements AuthenticationEntryPointInterface, AuthenticationFailureHandlerInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->unauthorized();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->unauthorized();
    }

    private function unauthorized(): JsonResponse
    {
        return new JsonResponse(
            ['error' => ['code' => 'unauthorized', 'message' => 'Please log in to continue.']],
            Response::HTTP_UNAUTHORIZED,
            // Standard header for 401: tells the client which scheme the API expects.
            ['WWW-Authenticate' => 'Bearer'],
        );
    }
}
