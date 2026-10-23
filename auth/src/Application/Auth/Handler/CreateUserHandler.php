<?php

namespace App\Application\Auth\Handler;

use App\Application\Auth\DTO\UserRequestDTO;
use App\Domain\Entity\User;
use App\Infrastructure\Doctrine\Repository\GroupRepository;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
        private UserPasswordHasherInterface $passwordHasher,
        private GroupRepository $groupRepo
    ) {}

    public function handle(UserRequestDTO $dto): void
    {
        if ($this->repo->existeConUsername($dto->username)) {
           throw new ConflictHttpException('Usuario ya existe');
        }

        $user = new User();
        $user->setUsername($dto->username);
        $user->setRoles($dto->roles ?: ['ROLE_USER']);
        $user->setGroups(array_values(array_filter(array_map(fn($id) => $this->groupRepo->find((int)$id), $dto->groupIds))));

        $hash = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hash);

        $this->repo->guardar($user);
    }
}
