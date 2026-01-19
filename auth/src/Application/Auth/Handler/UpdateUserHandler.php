<?php

namespace App\Application\Auth\Handler;

use App\Application\Auth\DTO\UserRequestDTO;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UpdateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function handle(int $id, UserRequestDTO $dto): void
    {
        $user = $this->repo->buscarPorId($id);

        if (!$user) {
            throw new \DomainException('Usuario no encontrado');
        }

        $user->setRoles($dto->roles ?: ['ROLE_USER']);

        if (!empty($dto->password)) {
            $hash = $this->passwordHasher->hashPassword($user, $dto->password);
            $user->setPassword($hash);
        }

        $this->repo->guardar($user);
    }
}
