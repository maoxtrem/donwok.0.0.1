<?php

namespace App\UI\Http\Controller;

use App\Application\Auth\DTO\LoginRequestDTO;
use App\Application\Auth\Handler\LoginHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AuthController
{
    #[Route('/auth/login', methods: ['POST'])]
    public function login(
        Request $request,
        LoginHandler $handler
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['username'], $data['password'])) {
            return new JsonResponse(['error' => 'JSON inválido'], 400);
        }

        try {
            $dto = new LoginRequestDTO($data['username'], $data['password']);
            $response = $handler->handle($dto);

            return new JsonResponse($response->toArray());
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
    }
}
