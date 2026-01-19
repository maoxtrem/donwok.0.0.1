<?php

namespace App\Application\Auth\Handler;

use App\Application\Auth\DTO\LoginRequestDTO;
use App\Application\Auth\DTO\AuthResponseDTO;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Security\TokenService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenService $tokenService
    ) {}

    public function handle(LoginRequestDTO $dto): AuthResponseDTO
    {
        $user = $this->userRepository->buscarPorUsername($dto->username);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw new \DomainException('Credenciales inválidas');
        }

        $roles = $user->getRoles();
        if (empty($roles)) {
            $roles = ['ROLE_USER'];
        }

        $token = $this->tokenService->sign([
            'uid' => $user->getId(),
            'username' => $user->getUserIdentifier(),
            'roles' => $roles,
        ]);

        return new AuthResponseDTO($token);
    }
}
