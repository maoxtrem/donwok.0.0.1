<?php

namespace App\UI\Http\Controller;

use App\Application\Auth\DTO\ServiceLoginRequestDTO;
use App\Application\Auth\Handler\ServiceLoginHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ServiceAuthController
{
    #[Route('/service/login', methods: ['POST'])]
    public function login(
        Request $request,
        ServiceLoginHandler $handler
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['service'], $data['secret'])) {
            return new JsonResponse(['error' => 'Invalid payload'], 400);
        }

        try {
            $token = $handler->handle(
                new ServiceLoginRequestDTO(
                    $data['service'],
                    $data['secret']
                )
            );

            return new JsonResponse(['token' => $token]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }
    }
}
