<?php

namespace App\UI\Http\Web\Controller;

use App\Application\DTO\Request\PedidoRequestDTO;
use App\Application\Handler\Pedido\CreatePedidoHandler;
use App\Infrastructure\Doctrine\Repository\FacturaRepository;
use App\Domain\Entity\Factura;
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
        if (!$pedido) return new JsonResponse(['message' => 'Pedido no encontrado'], 404);
        
        try {
            $pedido->marcarComoTerminado();
            $this->em->flush();
            $this->notifyCentrifugo($pedido, 'ORDER_READY');
            return new JsonResponse(['message' => 'Pedido terminado', 'pedido' => $pedido->toArray()]);
        } catch (\Exception $e) { return new JsonResponse(['message' => $e->getMessage()], 400); }
    }

    #[Route('/{id}/facturar', name: 'app_pedidos_facturar', methods: ['POST'])]
    public function facturar(int $id, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) return new JsonResponse(['message' => 'No encontrado'], 404);
        
        // Si ya está facturado, no hacemos nada más
        if ($pedido->getEstado() === Factura::ESTADO_FACTURADO) {
            return new JsonResponse(['message' => 'Este pedido ya fue facturado anteriormente']);
        }

        try {
            $proximoNumero = $repo->getNextInvoiceNumber();
            $pedido->facturar($proximoNumero);
            $this->em->flush();
            
            $this->notifyCentrifugo($pedido, 'ORDER_INVOICED');
            return new JsonResponse(['message' => 'Facturado con éxito: ' . $proximoNumero, 'pedido' => $pedido->toArray()]);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            // 🟢 Si hubo colisión de número, intentamos con el siguiente una vez más
            $proximoNumero = $repo->getNextInvoiceNumber();
            $pedido->facturar($proximoNumero);
            $this->em->flush();
            $this->notifyCentrifugo($pedido, 'ORDER_INVOICED');
            return new JsonResponse(['message' => 'Facturado tras colisión: ' . $proximoNumero, 'pedido' => $pedido->toArray()]);
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
            $this->notifyCentrifugo($factura, 'NEW_ORDER');
            return new JsonResponse(['message' => 'Pedido en cola', 'pedido' => $factura->toArray()], 201);
        } catch (\Exception $e) { return new JsonResponse(['message' => $e->getMessage()], 400); }
    }

    private function notifyCentrifugo(Factura $factura, string $type): void
    {
        $url = "http://centrifugo:8000/api";
        $data = [
            'method' => 'publish',
            'params' => [
                'channel' => 'public:pedidos', 
                'data' => ['type' => $type, 'pedido' => $factura->toArray()]
            ]
        ];

        $payload = json_encode($data);
        $opts = ['http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/json\r\nAuthorization: apikey api-key\r\n",
            'content' => $payload,
            'timeout' => 5
        ]];
        
        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            error_log("Centrifugo Error: No se pudo conectar al Hub.");
        } else {
            error_log("Centrifugo Success: Mensaje enviado -> " . $payload);
        }
    }
}