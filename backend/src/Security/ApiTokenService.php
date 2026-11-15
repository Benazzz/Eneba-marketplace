<?php

namespace App\Security;

use App\Entity\ApiToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Creates, checks and revokes login tokens (PLAN.md §6).
 *
 * The raw token is 32 random bytes, sent to the client once. The database only keeps its sha256 hash,
 * so a leaked database doesn't give anyone a usable token (the same idea as hashing passwords). A
 * fast hash is enough here, unlike for passwords, because the token is random and can't be guessed.
 */
final class ApiTokenService
{
    private const LIFETIME = '+7 days';

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * Saves a new token for the user and returns the raw value (the only time it exists outside the client).
     *
     * @return array{token: string, expiresAt: \DateTimeImmutable}
     */
    public function issue(User $user): array
    {
        $now = new \DateTimeImmutable();

        // Housekeeping: the user's expired tokens can never be used again, so they are deleted here.
        $this->em->createQuery('DELETE FROM App\Entity\ApiToken t WHERE t.user = :user AND t.expiresAt <= :now')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->execute();

        $raw = bin2hex(random_bytes(32)); // 64 hex characters
        $expiresAt = $now->modify(self::LIFETIME);

        $this->em->persist(new ApiToken($user, self::hash($raw), $expiresAt));
        $this->em->flush();

        return ['token' => $raw, 'expiresAt' => $expiresAt];
    }

    /** The stored token for this raw value, or null if it doesn't exist or has expired. */
    public function findValid(string $raw): ?ApiToken
    {
        $token = $this->em->getRepository(ApiToken::class)->findOneBy(['tokenHash' => self::hash($raw)]);

        if ($token === null || $token->isExpired(new \DateTimeImmutable())) {
            return null;
        }

        return $token;
    }

    /** Logout: deletes the token row, so the token stops working immediately. */
    public function revoke(string $raw): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\ApiToken t WHERE t.tokenHash = :hash')
            ->setParameter('hash', self::hash($raw))
            ->execute();
    }

    private static function hash(string $raw): string
    {
        return hash('sha256', $raw);
    }
}
