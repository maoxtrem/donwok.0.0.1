<?php

namespace App\Application\Auth\Handler;

use App\Application\Auth\DTO\UserRequestDTO;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Doctrine\Repository\GroupRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UpdateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repo,
        private UserPasswordHasherInterface $passwordHasher,
        private GroupRepository $groupRepo
    ) {}

    public function handle(int $id, UserRequestDTO $dto): void
    {
        $user = $this->repo->buscarPorId($id);

        if (!$user) {
            throw new \DomainException('Usuario no encontrado');
        }

        if ($dto->username !== '' && $dto->username !== $user->getUserIdentifier() && $this->repo->existeConUsername($dto->username)) {
            throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException('Usuario ya existe');
        }

        if ($dto->username !== '') {
            $user->setUsername($dto->username);
        }
        $isAdmin = in_array('ROLE_ADMIN', $dto->roles, true);
        $user->setRoles($isAdmin ? ['ROLE_ADMIN'] : ['ROLE_USER']);
        $user->setGroups($isAdmin ? [] : array_values(array_filter(array_map(fn($groupId) => $this->groupRepo->find((int)$groupId), $dto->groupIds))));

        if (!empty($dto->password)) {
            $hash = $this->passwordHasher->hashPassword($user, $dto->password);
            $user->setPassword($hash);
        }

        $this->repo->guardar($user);
    }
}
