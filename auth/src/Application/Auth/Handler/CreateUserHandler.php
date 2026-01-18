<?php

namespace App\Application\Auth\Handler;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function handle(
        string $username,
        string $plainPassword,
        array $roles = []
    ): User {
        if ($this->userRepository->existeConUsername($username)) {
            throw new \RuntimeException('El usuario ya existe');
        }

        $user = new User(
            username: $username,
            roles: $roles
        );

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plainPassword
        );

        $user->setPassword($hashedPassword);

        $this->userRepository->guardar($user);

        return $user;
    }
}
