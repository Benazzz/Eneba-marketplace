<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A registered customer. Table is "users" because "user" is a reserved word in PostgreSQL.
 * Implements Symfony's security interfaces so the password hasher (and later the
 * login / token authenticator) can work with it.
 */
#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    /** Hashed by Symfony's password hasher, never the plain password. */
    #[ORM\Column]
    private string $password = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $email)
    {
        $this->email = strtolower(trim($email));
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /** Takes an already hashed password (see UserPasswordHasherInterface::hashPassword). */
    public function setPassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** The value that identifies the user when logging in. */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** Every account is a plain customer; there are no admin roles in this app. */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    /** Required by UserInterface, deprecated since Symfony 7.3. We keep no plain password in memory. */
    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
