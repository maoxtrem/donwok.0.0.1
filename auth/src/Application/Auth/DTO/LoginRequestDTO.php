<?php

namespace App\Application\Auth\DTO;

final class LoginRequestDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $password
    ) {}
}
