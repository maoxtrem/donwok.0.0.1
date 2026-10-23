<?php

namespace App\UI\Http\Controller;

use App\Domain\Entity\Group;
use App\Infrastructure\Doctrine\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/service/groups')]
class GroupController
{
    public function __construct(private GroupRepository $repo, private EntityManagerInterface $em) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse { return new JsonResponse(array_map(fn(Group $g) => $g->toArray(), $this->repo->listar())); }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $group = new Group((string)($data['nombre'] ?? ''), is_array($data['urls'] ?? null) ? $data['urls'] : []);
        $this->em->persist($group); $this->em->flush();
        return new JsonResponse($group->toArray(), 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $group = $this->repo->buscarPorId($id);
        if (!$group) return new JsonResponse(['message' => 'Grupo no encontrado.'], 404);
        $data = json_decode($request->getContent(), true) ?? [];
        $group->actualizar((string)($data['nombre'] ?? ''), is_array($data['urls'] ?? null) ? $data['urls'] : []);
        $this->em->flush();
        return new JsonResponse($group->toArray());
    }
}
