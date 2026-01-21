<?php

namespace App\UI\Http\Web\Controller;

use App\Application\DTO\Request\PedidoRequestDTO;
use App\Application\Handler\Pedido\CreatePedidoHandler;
use App\Application\Handler\Pedido\EliminarPedidoHandler;
use App\Infrastructure\Doctrine\Repository\FacturaRepository;
use App\Domain\Entity\Factura;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[Route('/pedidos')]
class PedidoController extends AbstractController
{
    public function __construct(
        private CreatePedidoHandler $createHandler,
        private EliminarPedidoHandler $eliminarHandler,
        private EntityManagerInterface $em,
        private HubInterface $hub
    ) {}

    #[Route('/stats', name: 'app_pedidos_stats', methods: ['GET'])]
    public function stats(FacturaRepository $repo): JsonResponse
    {
        $pendientes = $repo->count(['estado' => Factura::ESTADO_PENDIENTE]);
        $terminados = $repo->count(['estado' => Factura::ESTADO_TERMINADO]);

        return new JsonResponse([
            'pendientes' => $pendientes,
            'terminados' => $terminados
        ]);
    }

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
        if (!$pedido) return new JsonResponse(['message' => 'Pedido no encontrado'], 404);
        
        try {
            $pedido->marcarComoTerminado();
            $this->em->flush();
            $this->publishUpdate($pedido, 'ORDER_READY');
            return new JsonResponse(['message' => 'Pedido terminado', 'pedido' => $pedido->toArray()]);
        } catch (\Exception $e) { 
            error_log('Error terminando pedido: ' . $e->getMessage());
            return new JsonResponse(['message' => $e->getMessage()], 400); 
        }
    }

    #[Route('/{id}/facturar', name: 'app_pedidos_facturar', methods: ['POST'])]
    public function facturar(int $id, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) return new JsonResponse(['message' => 'No encontrado'], 404);
        
        if ($pedido->getEstado() === Factura::ESTADO_FACTURADO) {
            return new JsonResponse(['message' => 'Este pedido ya fue facturado anteriormente']);
        }

        try {
            $proximoNumero = $repo->getNextInvoiceNumber();
            $pedido->facturar($proximoNumero);
            $this->em->flush();
            
            $this->publishUpdate($pedido, 'ORDER_INVOICED');
            return new JsonResponse(['message' => 'Facturado con éxito: ' . $proximoNumero, 'pedido' => $pedido->toArray()]);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            $proximoNumero = $repo->getNextInvoiceNumber();
            $pedido->facturar($proximoNumero);
            $this->em->flush();
            $this->publishUpdate($pedido, 'ORDER_INVOICED');
            return new JsonResponse(['message' => 'Facturado tras colisión: ' . $proximoNumero, 'pedido' => $pedido->toArray()]);
        } catch (\Exception $e) { 
            return new JsonResponse(['message' => $e->getMessage()], 400); 
        }
    }

    #[Route('/{id}/eliminar', name: 'app_pedidos_eliminar', methods: ['DELETE'])]
    public function eliminar(int $id): JsonResponse
    {
        try {
            $this->eliminarHandler->handle($id);
            // Publicar actualización para que el monitor/gestión lo quite
            $update = new Update(
                'donwok/pedidos',
                json_encode(['type' => 'ORDER_DELETED', 'pedidoId' => $id])
            );
            $this->hub->publish($update);

            return new JsonResponse(['message' => 'Pedido eliminado con éxito']);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('', name: 'app_ui_http_web_pedido_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new PedidoRequestDTO($data['items'] ?? []);
        try {
            $factura = $this->createHandler->handle($dto);
            $this->publishUpdate($factura, 'NEW_ORDER');
            return new JsonResponse(['message' => 'Pedido en cola', 'pedido' => $factura->toArray()], 201);
        } catch (\Exception $e) { return new JsonResponse(['message' => $e->getMessage()], 400); }
    }

    private function publishUpdate(Factura $factura, string $type): void
    {
        // Mercure HubInterface se encarga de firmar el JWT automáticamente
        $update = new Update(
            'donwok/pedidos',
            json_encode(['type' => $type, 'pedido' => $factura->toArray()])
        );

        $this->hub->publish($update);
    }
}