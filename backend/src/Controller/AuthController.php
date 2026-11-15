<?php

namespace App\Controller;

use App\Dto\LoginRequest;
use App\Dto\RegisterRequest;
use App\Entity\User;
use App\Security\ApiTokenService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\AccessToken\HeaderAccessTokenExtractor;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Register, login, logout and "who am I" (PLAN.md §4, §6).
 * Which routes need a token is decided in security.yaml (access_control), not here.
 */
final class AuthController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ApiTokenService $tokens,
    ) {
    }

    /** POST /api/auth/register { email, password } → 201 { token, expiresAt, user } */
    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $body): JsonResponse
    {
        // The User constructor trims and lowercases the email, so "Demo@Example.com" is found as a duplicate.
        $user = new User($body->email);

        if ($this->em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]) !== null) {
            return $this->emailTaken();
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $body->password));
        $this->em->persist($user);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            // Two sign-ups with the same email at the same moment: both passed the check above,
            // and the unique index in the database stopped the second one.
            return $this->emailTaken();
        }

        return $this->session($user, Response::HTTP_CREATED);
    }

    /** POST /api/auth/login { email, password } → 200 { token, expiresAt, user } / 401 */
    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(#[MapRequestPayload] LoginRequest $body): JsonResponse
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => strtolower(trim($body->email))]);

        if ($user === null) {
            // Hashing is deliberately slow (~50–100 ms). Without this line, an unknown email would answer
            // much faster than a wrong password, which would tell an attacker which emails are registered.
            $this->passwordHasher->hashPassword(new User('unknown@example.invalid'), $body->password);

            return $this->invalidCredentials();
        }

        if (!$this->passwordHasher->isPasswordValid($user, $body->password)) {
            return $this->invalidCredentials();
        }

        return $this->session($user, Response::HTTP_OK);
    }

    /** POST /api/auth/logout 🔒 → 204. Deletes the token, so it stops working right away. */
    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(Request $request): Response
    {
        // The firewall has already checked this token. Symfony's own extractor reads it from
        // "Authorization: Bearer <token>" the same way the authenticator did.
        $rawToken = (new HeaderAccessTokenExtractor())->extractAccessToken($request);
        if ($rawToken !== null) {
            $this->tokens->revoke($rawToken);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /** GET /api/me 🔒 → { id, email } */
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        // #[CurrentUser] injects the logged-in user, like HttpContext.User in ASP.NET (but as our entity).
        return new JsonResponse($this->userJson($user));
    }

    /** Issues a new token and returns it with the user. Shared by register and login. */
    private function session(User $user, int $status): JsonResponse
    {
        $issued = $this->tokens->issue($user);

        return new JsonResponse([
            'token' => $issued['token'],
            'expiresAt' => $issued['expiresAt']->format(\DATE_ATOM),
            'user' => $this->userJson($user),
        ], $status);
    }

    /** @return array{id: int, email: string} */
    private function userJson(User $user): array
    {
        return ['id' => (int) $user->getId(), 'email' => $user->getEmail()];
    }

    private function invalidCredentials(): JsonResponse
    {
        // One message for both cases, so the response never reveals whether the email exists.
        return new JsonResponse(
            ['error' => ['code' => 'invalid_credentials', 'message' => 'Wrong email or password.']],
            Response::HTTP_UNAUTHORIZED,
        );
    }

    private function emailTaken(): JsonResponse
    {
        return new JsonResponse(['error' => [
            'code' => 'email_taken',
            'message' => 'Please check the highlighted fields.',
            'fields' => ['email' => 'An account with this email already exists.'],
        ]], Response::HTTP_CONFLICT);
    }
}
