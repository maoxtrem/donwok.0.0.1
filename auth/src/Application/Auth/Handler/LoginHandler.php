<?php

namespace App\Application\Auth\Handler;

use App\Application\Auth\DTO\LoginRequestDTO;
use App\Application\Auth\Security\JwtPayloadFactory;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Security\TokenService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenService $tokenService,
        private JwtPayloadFactory $payloadFactory
    ) {}

    public function handle(LoginRequestDTO $dto): string
    {
        $user = $this->userRepository->buscarPorUsername($dto->username);

        if (!$user) {
            throw new \RuntimeException('Credenciales inválidas');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw new \RuntimeException('Credenciales inválidas');
        }

        $payload = $this->payloadFactory->create($user);

        return $this->tokenService->sign($payload);
    }
}
