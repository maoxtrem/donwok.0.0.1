<?php

namespace App\Application\Auth\DTO;

class UserResponseDTO
{
    public function __construct(
        public int $id,
        public string $username,
        public array $roles,
        public array $groups = []
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'roles' => $this->roles,
            'groups' => $this->groups,
        ];
    }
}
