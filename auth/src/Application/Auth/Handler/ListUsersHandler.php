<?php

namespace App\Application\Auth\Handler;

use App\Application\Auth\DTO\UserResponseDTO;
use App\Domain\Repository\UserRepositoryInterface;

class ListUsersHandler
{
    public function __construct(private UserRepositoryInterface $repo) {}

    public function handle(): array
    {
        return array_map(
            fn($u) => new UserResponseDTO(
                $u->getId(),
                $u->getUserIdentifier(),
                $u->getRoles()
            ),
            $this->repo->buscarTodos()
        );
    }
}
