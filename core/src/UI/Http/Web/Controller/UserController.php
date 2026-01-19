<?php

namespace App\UI\Http\Web\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserController
{
    #[Route('/usuarios', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // 🚧 Lógica vendrá después
        return new JsonResponse([
            'message' => 'Crear usuario (pendiente)',
            'data' => $data
        ], 201);
    }

    #[Route('/usuarios/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        return new JsonResponse([
            'message' => 'Actualizar usuario (pendiente)',
            'id' => $id,
            'data' => $data
        ]);
    }

    #[Route('/usuarios/{id}', methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Eliminar usuario (pendiente)',
            'id' => $id
        ], 204);
    }
}
