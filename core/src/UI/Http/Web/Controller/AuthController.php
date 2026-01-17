<?php

namespace App\UI\Http\Web\Controller;

use App\Infrastructure\Security\TokenVerifierService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AuthController
{
    public function __construct(private TokenVerifierService $tokenVerifier) {}

    #[Route('/login-by-token', methods: ['POST'])]
    public function loginByToken(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'No token provided'], 401);
        }

        $token = substr($authHeader, 7);

        $payload = $this->tokenVerifier->verify($token);
        if (!$payload) {
            return new JsonResponse(['error' => 'Token invalido'], 401);
        }

        // Crear sesión Symfony
        $session = $request->getSession();
        $session->set('user_id', $payload['uid'] ?? null);
        $session->set('username', $payload['username'] ?? null);
        $session->set('roles', $payload['roles'] ?? ['ROLE_USER']);

        return new JsonResponse([
            'message' => 'Sesión creada correctamente',
            'session_id' => $session->getId(),
            'user' => $payload
        ]);
    }

    // Ejemplo de ruta protegida por sesión
    #[Route('/me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return new JsonResponse(['error' => 'No session'], 401);
        }

        return new JsonResponse([
            'user' => [
                'id' => $session->get('user_id'),
                'email' => $session->get('username'),
                'roles' => $session->get('roles'),
            ]
        ]);
    }

    // Logout de sesión
    #[Route('/logout', methods: ['GET'])]
    public function logout(Request $request): JsonResponse
    {
        $request->getSession()->invalidate();
        return new JsonResponse(['message' => 'Sesión cerrada correctamente']);
    }
}
