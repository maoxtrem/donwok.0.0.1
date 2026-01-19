<?php

namespace App\Application\Auth\DTO;

final class ServiceLoginRequestDTO
{
    public function __construct(
        public readonly string $service,
        public readonly string $secret
    ) {}
}
