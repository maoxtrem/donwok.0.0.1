<?php

namespace App\UI\Http\Web\Controller;

use App\Application\DTO\Request\PedidoRequestDTO;
use App\Application\Handler\Pedido\CreatePedidoHandler;
use App\Domain\Repository\PedidoRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pedidos')]
class PedidoController extends AbstractController
{
    public function __construct(
        private CreatePedidoHandler $createHandler
    ) {}

    #[Route('/pendientes', methods: ['GET'])]
    public function pendientes(PedidoRepositoryInterface $repo): JsonResponse
    {
        $pedidos = $repo->findPendientes();
        return new JsonResponse(
            array_map(fn($p) => $p->toArray(), $pedidos)
        );
    }

    #[Route('/{id}/procesar', methods: ['POST'])]
    public function procesar(int $id, PedidoRepositoryInterface $repo): JsonResponse
    {
        $pedido = $repo->buscarPorId($id);
        if (!$pedido) return new JsonResponse(['message' => 'No existe'], 404);
        
        $pedido->vincularFactura(new \App\Domain\Entity\Factura()); // Simplificado para la prueba
        $repo->guardar($pedido);

        return new JsonResponse(['message' => 'Pedido procesado']);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        
        $dto = new PedidoRequestDTO($data['items'] ?? []);
        
        try {
            $pedido = $this->createHandler->handle($dto);
            return new JsonResponse([
                'message' => 'Pedido en cola correctamente',
                'pedido' => $pedido->toArray()
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}