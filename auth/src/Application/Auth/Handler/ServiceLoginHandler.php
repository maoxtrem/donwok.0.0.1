<?php

namespace App\Application\Auth\Handler;

use App\Application\Auth\DTO\ServiceLoginRequestDTO;
use App\Application\Auth\Security\ServiceJwtPayloadFactory;
use App\Infrastructure\Security\TokenService;

final class ServiceLoginHandler
{
    public function __construct(
        private TokenService $tokenService,
        private ServiceJwtPayloadFactory $payloadFactory,
        private string $coreServiceName,
        private string $coreServiceSecret
    ) {}

    public function handle(ServiceLoginRequestDTO $dto): string
    {
        if (
            $dto->service !== $this->coreServiceName ||
            !hash_equals($this->coreServiceSecret, $dto->secret)
        ) {
            throw new \RuntimeException('Service credentials invalid');
        }

        $payload = $this->payloadFactory->create($dto->service);

        return $this->tokenService->sign($payload);
    }
}
