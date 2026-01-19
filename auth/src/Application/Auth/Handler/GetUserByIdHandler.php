<?php

namespace App\Application\Auth\Handler;

use App\Application\Auth\DTO\UserResponseDTO;
use App\Domain\Repository\UserRepositoryInterface;

class GetUserByIdHandler
{
    public function __construct(private UserRepositoryInterface $repo) {}

    public function handle(int $id): UserResponseDTO
    {
        $user = $this->repo->buscarPorId($id);

        if (!$user) {
            throw new \DomainException('Usuario no encontrado');
        }

        return new UserResponseDTO(
            $user->getId(),
            $user->getUserIdentifier(),
            $user->getRoles()
        );
    }
}
