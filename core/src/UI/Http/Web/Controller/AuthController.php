<?php

namespace App\UI\Http\Web\Controller;

use App\Infrastructure\Security\TokenVerifierService;
use App\Infrastructure\Security\CoreUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthController
{
    public function __construct(
        private TokenVerifierService $tokenVerifier,
        private TokenStorageInterface $tokenStorage
    ) {}

    #[Route('/login-by-token', methods: ['POST'])]
    public function loginByToken(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'No token provided'], 401);
        }

        $jwt = substr($authHeader, 7);

        $payload = $this->tokenVerifier->verify($jwt);
        if (!$payload) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }

        // 👤 Usuario de seguridad (NO entidad)
        $user = new CoreUser(
            $payload['uid'],
            $payload['username'],
            $payload['roles'] ?? ['ROLE_USER'],
            $payload['allowed_urls'] ?? []
        );

        // 🔐 Token de Symfony
        $token = new UsernamePasswordToken(
            $user,
            'main', // firewall
            $user->getRoles()
        );

        // ✅ Autenticar al usuario
        $this->tokenStorage->setToken($token);

        return new JsonResponse([
            'message' => 'Sesión creada correctamente',
            'user' => [
                'id' => $payload['uid'],
                'username' => $payload['username'],
                'roles' => $payload['roles'],
                'allowed_urls' => $payload['allowed_urls'] ?? [],
                'start_url' => $payload['start_url'] ?? '/dashboard',
            ]
        ]);
    }

    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $token = $this->tokenStorage->getToken();

        if (!$token || !$token->getUser()) {
            return new JsonResponse(['error' => 'No autenticado'], 401);
        }

        $user = $token->getUser();

        return new JsonResponse([
            'user' => [
                'username' => $user->getUserIdentifier(),
                'roles' => $user->getRoles(),
                'allowed_urls' => $user->getAllowedUrls(),
            ]
        ]);
    }

    #[Route('/logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $this->tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        return new JsonResponse(['message' => 'Sesión cerrada']);
    }

    #[Route('/session/logout', methods: ['POST'])]
    public function sessionLogout(Request $request): JsonResponse
    {
        $this->tokenStorage->setToken(null);
        if ($request->hasSession()) {
            $request->getSession()->invalidate();
        }

        return new JsonResponse(['message' => 'Sesión cerrada']);
    }
}
