<?php

namespace App\UI\Http\Web\Controller;

use App\Infrastructure\Security\AuthServiceTokenProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/usuarios')]
class UserController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private AuthServiceTokenProvider $tokenProvider,
        private string $authBaseUrl,
        private Security $security,
    ) {}

    /**
     * Crear usuario
     */
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        if ($response = $this->denyUnlessCanManageUsers()) return $response;
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'JSON inválido'], 400);
        }

        $data['roles'] = $this->isAdmin() ? ($data['roles'] ?? ['ROLE_USER']) : ['ROLE_USER'];

        $token = $this->tokenProvider->getToken();

        $response = $this->httpClient->request('POST', $this->authBaseUrl . '/service/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'json' => $data,
        ]);
        return new JsonResponse(
            $response->toArray(false),
            $response->getStatusCode()
        );
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        if ($response = $this->denyUnlessCanManageUsers()) return $response;
        $token = $this->tokenProvider->getToken();
        $response = $this->httpClient->request('GET', $this->authBaseUrl . '/service/users', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);
        return new JsonResponse($response->toArray(false), $response->getStatusCode());
    }

    /**
     * Actualizar usuario
     */
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if ($response = $this->denyUnlessCanManageUsers()) return $response;
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'JSON inválido'], 400);
        }

        $data['roles'] = $this->isAdmin() ? ($data['roles'] ?? ['ROLE_USER']) : ['ROLE_USER'];

        $token = $this->tokenProvider->getToken();

        $response = $this->httpClient->request('PUT', $this->authBaseUrl . "/service/users/{$id}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'json' => $data,
        ]);

        return new JsonResponse(
            $response->toArray(false),
            $response->getStatusCode()
        );
    }

    /**
     * Eliminar usuario
     */
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        if ($response = $this->denyUnlessCanManageUsers()) return $response;
        $token = $this->tokenProvider->getToken();

        $response = $this->httpClient->request('DELETE', $this->authBaseUrl . "/service/users/{$id}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return new JsonResponse(
            null,
            $response->getStatusCode()
        );
    }

    #[Route('/grupos', methods: ['GET'])]
    public function groups(): JsonResponse
    {
        if ($response = $this->denyUnlessCanManageUsers()) return $response;
        $token = $this->tokenProvider->getToken();
        $response = $this->httpClient->request('GET', $this->authBaseUrl . '/service/groups', ['headers' => ['Authorization' => 'Bearer ' . $token]]);
        return new JsonResponse($response->toArray(false), $response->getStatusCode());
    }

    #[Route('/grupos', methods: ['POST'])]
    public function createGroup(Request $request): JsonResponse
    {
        if ($response = $this->denyUnlessAdmin()) return $response;
        $token = $this->tokenProvider->getToken();
        $response = $this->httpClient->request('POST', $this->authBaseUrl . '/service/groups', ['headers' => ['Authorization' => 'Bearer ' . $token], 'json' => json_decode($request->getContent(), true) ?? []]);
        return new JsonResponse($response->toArray(false), $response->getStatusCode());
    }

    #[Route('/grupos/{id}', methods: ['PUT'])]
    public function updateGroup(int $id, Request $request): JsonResponse
    {
        if ($response = $this->denyUnlessAdmin()) return $response;
        $token = $this->tokenProvider->getToken();
        $response = $this->httpClient->request('PUT', $this->authBaseUrl . "/service/groups/{$id}", ['headers' => ['Authorization' => 'Bearer ' . $token], 'json' => json_decode($request->getContent(), true) ?? []]);
        return new JsonResponse($response->toArray(false), $response->getStatusCode());
    }

    private function isAdmin(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canManageUsers(): bool
    {
        if ($this->isAdmin()) return true;
        $user = $this->security->getUser();
        if (!$user instanceof \App\Infrastructure\Security\CoreUser) return false;
        return in_array('/usuarios', $user->getAllowedUrls(), true);
    }

    private function denyUnlessCanManageUsers(): ?JsonResponse
    {
        return $this->canManageUsers() ? null : new JsonResponse(['error' => 'No tiene permiso para gestionar usuarios.'], 403);
    }

    private function denyUnlessAdmin(): ?JsonResponse
    {
        return $this->isAdmin() ? null : new JsonResponse(['error' => 'Solo el administrador puede gestionar grupos.'], 403);
    }
}
