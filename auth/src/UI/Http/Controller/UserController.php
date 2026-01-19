<?php

namespace App\UI\Http\Controller;

use App\Application\Auth\DTO\UserRequestDTO;
use App\Application\Auth\Handler\CreateUserHandler;
use App\Application\Auth\Handler\UpdateUserHandler;
use App\Application\Auth\Handler\DeleteUserHandler;
use App\Application\Auth\Handler\GetUserByIdHandler;
use App\Application\Auth\Handler\ListUsersHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/service/users')]
class UserController
{
    #[Route('', methods: ['POST'])]
    public function create(Request $request, CreateUserHandler $handler): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $handler->handle(
            new UserRequestDTO(
                $data['username'],
                $data['password'],
                $data['roles'] ?? []
            )
        );

        return new JsonResponse(['message' => 'Usuario creado'], 201);
    }

    #[Route('', methods: ['GET'])]
    public function list(ListUsersHandler $handler): JsonResponse
    {
        $users = array_map(fn($u) => $u->toArray(), $handler->handle());
        return new JsonResponse($users);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id, GetUserByIdHandler $handler): JsonResponse
    {
        return new JsonResponse($handler->handle($id)->toArray());
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, UpdateUserHandler $handler): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $handler->handle(
            $id,
            new UserRequestDTO(
                $data['username'] ?? '',
                $data['password'] ?? '',
                $data['roles'] ?? []
            )
        );

        return new JsonResponse(['message' => 'Usuario actualizado']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, DeleteUserHandler $handler): JsonResponse
    {
        $handler->handle($id);
        return new JsonResponse(['message' => 'Usuario eliminado']);
    }
}
