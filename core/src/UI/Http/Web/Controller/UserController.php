<?php

namespace App\UI\Http\Web\Controller;

use App\Infrastructure\Security\AuthServiceTokenProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/usuarios')]
class UserController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private AuthServiceTokenProvider $tokenProvider,
        private string $authBaseUrl,
    ) {}

    /**
     * Crear usuario
     */
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'JSON inválido'], 400);
        }
    return new JsonResponse(
       $data
        );
        $token = $this->tokenProvider->getToken();
        
        $response = $this->httpClient->request('POST', $this->authBaseUrl . '/service/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'json' => $data,
        ]);
        return new JsonResponse(
        [],
            $response->getStatusCode()
        );
    }

    /**
     * Actualizar usuario
     */
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'JSON inválido'], 400);
        }

        $token = $this->tokenProvider->getToken();

        $response = $this->httpClient->request('PUT', "/service/users/{$id}", [
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
        $token = $this->tokenProvider->getToken();

        $response = $this->httpClient->request('DELETE', "/service/users/{$id}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return new JsonResponse(
            null,
            $response->getStatusCode()
        );
    }
}
