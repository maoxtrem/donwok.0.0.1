<?php

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class CoreUser implements UserInterface
{
    public function __construct(
        private int $id,
        private string $username,
        private array $roles,
        private array $allowedUrls = []
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getRoles(): array
    {
        return $this->roles ?: ['ROLE_USER'];
    }

    public function getAllowedUrls(): array { return $this->allowedUrls; }

    public function eraseCredentials(): void {}
}
