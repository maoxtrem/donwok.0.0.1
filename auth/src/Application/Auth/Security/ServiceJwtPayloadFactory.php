<?php

namespace App\Application\Auth\Security;

final class ServiceJwtPayloadFactory
{
    public function create(string $service): array
    {
        return [
            'sub' => $service,
            'type' => 'service',
            'roles' => ['ROLE_SERVICE'],
            'iat' => time(),
        ];
    }
}
