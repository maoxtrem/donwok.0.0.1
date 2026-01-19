<?php 

namespace App\Application\Auth\DTO;

class UserRequestDTO
{
    public function __construct(
        public string $username,
        public string $password,
        public array $roles = []
    ) {}
}
