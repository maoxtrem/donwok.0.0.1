<?php

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class CoreUser implements UserInterface
{
    public function __construct(
        private int $id,
        private string $username,
        private array $roles
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getRoles(): array
    {
        return $this->roles ?: ['ROLE_USER'];
    }

    public function eraseCredentials(): void {}
}
