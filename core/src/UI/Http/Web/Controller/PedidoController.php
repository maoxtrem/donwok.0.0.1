<?php

namespace App\UI\Http\Web\Controller;

use App\Application\DTO\Request\PedidoRequestDTO;
use App\Application\Handler\Pedido\CreatePedidoHandler;
use App\Domain\Entity\Factura;
use App\Infrastructure\Doctrine\Repository\FacturaRepository;
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

    #[Route('/pendientes', name: 'app_pedidos_pendientes', methods: ['GET'])]
    public function pendientes(FacturaRepository $repo): JsonResponse
    {
        $pedidos = $repo->findBy(
            ['estado' => [Factura::ESTADO_PENDIENTE, Factura::ESTADO_TERMINADO]],
            ['id' => 'DESC']
        );
        return new JsonResponse(array_map(fn($p) => $p->toArray(), $pedidos));
    }

    #[Route('/{id}/terminar', name: 'app_pedidos_terminar', methods: ['POST'])]
    public function terminar(int $id, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) return new JsonResponse(['message' => 'No encontrado'], 404);
        
        try {
            $pedido->marcarComoTerminado();
            $this->em->flush();
            $this->notifyMercure($pedido, 'ORDER_READY');
            return new JsonResponse(['message' => 'Pedido terminado', 'pedido' => $pedido->toArray()]);
        } catch (\Exception $e) { return new JsonResponse(['message' => $e->getMessage()], 400); }
    }

    #[Route('/{id}/facturar', name: 'app_pedidos_facturar', methods: ['POST'])]
    public function facturar(int $id, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) return new JsonResponse(['message' => 'No encontrado'], 404);
        
        try {
            $proximoNumero = $repo->getNextInvoiceNumber();
            $pedido->facturar($proximoNumero);
            $this->em->flush();
            $this->notifyMercure($pedido, 'ORDER_INVOICED');
            return new JsonResponse(['message' => 'Facturado con éxito: ' . $proximoNumero, 'pedido' => $pedido->toArray()]);
        } catch (\Exception $e) { return new JsonResponse(['message' => $e->getMessage()], 400); }
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new PedidoRequestDTO($data['items'] ?? []);
        try {
            $factura = $this->createHandler->handle($dto);
            $this->notifyMercure($factura, 'NEW_ORDER');
            return new JsonResponse(['message' => 'Pedido en cola', 'pedido' => $factura->toArray()], 201);
        } catch (\Exception $e) { return new JsonResponse(['message' => $e->getMessage()], 400); }
    }

    private function notifyMercure(Factura $factura, string $type): void
    {
        $url = "http://mercure/.well-known/mercure";
        $data = http_build_query([
            'topic' => 'donwok/pedidos',
            'data'  => json_encode(['type' => $type, 'pedido' => $factura->toArray()])
        ]);
        $opts = ['http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Bearer !ChangeThisMercureHubFreeApp2022!\r\n",
            'content' => $data,
            'timeout' => 2
        ]];
        try { file_get_contents($url, false, stream_context_create($opts)); } catch (\Exception $e) {}
    }
}