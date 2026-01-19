<?php

namespace App\Application\Auth\DTO;

class AuthResponseDTO
{
    public function __construct(
        public string $token
    ) {}

    public function toArray(): array
    {
        return ['token' => $this->token];
    }
}
