<?php

namespace App\Application\Auth\Security;

use App\Domain\Entity\User;

final class JwtPayloadFactory
{
    public function create(User $user): array
    {
        $roles = $user->getRoles();

        // 🔐 Regla clara: solo si no hay roles
        if (empty($roles)) {
            $roles = ['ROLE_USER'];
        }

        return [
            'uid'      => $user->getId(),
            'username' => $user->getUserIdentifier(),
            'roles'    => array_values(array_unique($roles)),
            'iat'      => time(),
        ];
    }
}
