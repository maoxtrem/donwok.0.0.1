<?php

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class CoreUserProvider implements UserProviderInterface
{
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // ❗ NO se usa (no hay refresh desde BD)
        throw new UserNotFoundException('Stateless user');
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof CoreUser) {
            throw new \InvalidArgumentException('User not supported');
        }

        // 🔥 IMPORTANTE:
        // devolvemos el MISMO usuario (viene del token)
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return $class === CoreUser::class;
    }
}
