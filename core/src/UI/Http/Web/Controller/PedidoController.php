<?php

namespace App\UI\Http\Web\Controller;

use App\Application\DTO\Request\PedidoRequestDTO;
use App\Application\Handler\Pedido\CreatePedidoHandler;
use App\Domain\Entity\Venta;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pedidos')]
class PedidoController extends AbstractController
{
    public function __construct(
        private CreatePedidoHandler $createHandler,
        private EntityManagerInterface $em
    ) {}

    #[Route('/pendientes', methods: ['GET'])]
    public function pendientes(): JsonResponse
    {
        $ventas = $this->em->getRepository(Venta::class)->findBy(
            ['estado' => [Venta::ESTADO_PENDIENTE, Venta::ESTADO_TERMINADO]],
            ['id' => 'DESC']
        );

        return new JsonResponse(
            array_map(fn($v) => $v->toArray(), $ventas)
        );
    }

    #[Route('/{id}/terminar', methods: ['POST'])]
    public function terminar(int $id): JsonResponse
    {
        $venta = $this->em->getRepository(Venta::class)->find($id);
        if (!$venta) return new JsonResponse(['message' => 'Venta no encontrada'], 404);
        
        try {
            $venta->marcarComoTerminado();
            $this->em->flush();
            $this->notifyMercure($venta, 'ORDER_READY');
            return new JsonResponse(['message' => 'Pedido terminado']);
        } catch (
Exception $e) { return new JsonResponse(['message' => $e->getMessage()], 400); }
    }

    #[Route('/{id}/facturar', name: 'app_pedidos_facturar', methods: ['POST'])]
    public function facturar(int $id): JsonResponse
    {
        $repo = $this->em->getRepository(Venta::class);
        $venta = $repo->find($id);
        
        if (!$venta) return new JsonResponse(['message' => 'Venta no encontrada'], 404);
        
        try {
            $proximoNumero = $repo->getNextInvoiceNumber();
            $venta->facturar($proximoNumero);
            $this->em->flush();
            
            $this->notifyMercure($venta, 'ORDER_INVOICED');
            return new JsonResponse([
                'message' => 'Venta facturada con éxito: ' . $proximoNumero,
                'pedido' => $venta->toArray()
            ]);
        } catch (\Exception $e) { return new JsonResponse(['message' => $e->getMessage()], 400); }
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new PedidoRequestDTO($data['items'] ?? []);
        
        try {
            $venta = $this->createHandler->handle($dto);
            $this->notifyMercure($venta, 'NEW_ORDER');
            return new JsonResponse([
                'message' => 'Pedido en cola correctamente',
                'pedido' => $venta->toArray()
            ], JsonResponse::HTTP_CREATED);
        } catch (
Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    private function notifyMercure(Venta $venta, string $type): void
    {
        $url = "http://mercure/.well-known/mercure";
        $data = http_build_query([
            'topic' => 'donwok/pedidos',
            'data'  => json_encode(['type' => $type, 'pedido' => $venta->toArray()])
        ]);

        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n" .
                             "Authorization: Bearer !ChangeThisMercureHubFreeApp2022!\r\n",
                'content' => $data,
                'timeout' => 2
            ]
        ];

        try { file_get_contents($url, false, stream_context_create($opts)); } catch (
Exception $e) {}
    }
}
