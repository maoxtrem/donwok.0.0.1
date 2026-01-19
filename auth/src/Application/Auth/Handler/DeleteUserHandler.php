<?php

namespace App\Application\Auth\Handler;

use App\Domain\Repository\UserRepositoryInterface;

class DeleteUserHandler
{
    public function __construct(private UserRepositoryInterface $repo) {}

    public function handle(int $id): void
    {
        $user = $this->repo->buscarPorId($id);

        if (!$user) {
            throw new \DomainException('Usuario no encontrado');
        }

        $this->repo->eliminar($user);
    }
}
