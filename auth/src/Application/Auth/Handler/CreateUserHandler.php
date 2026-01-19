<?php

namespace App\Application\Auth\Handler;

use App\Application\Auth\DTO\UserRequestDTO;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function handle(UserRequestDTO $dto): void
    {
        if ($this->repo->existeConUsername($dto->username)) {
            throw new \DomainException('Usuario ya existe');
        }

        $user = new User();
        $user->setUsername($dto->username);
        $user->setRoles($dto->roles ?: ['ROLE_USER']);

        $hash = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hash);

        $this->repo->guardar($user);
    }
}
