<?php

namespace App\UI\Http\Web\Controller;

use App\Application\DTO\Request\PedidoRequestDTO;
use App\Application\Handler\Pedido\CreatePedidoHandler;
use App\Application\Handler\Pedido\EliminarPedidoHandler;
use App\Domain\Entity\DatosEmpresa;
use App\Infrastructure\Doctrine\Repository\FacturaRepository;
use App\Domain\Entity\Factura;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Process\Process;

#[Route('/pedidos')]
class PedidoController extends AbstractController
{
    public function __construct(
        private CreatePedidoHandler $createHandler,
        private EliminarPedidoHandler $eliminarHandler,
        private EntityManagerInterface $em,
        private HubInterface $hub,
        private string $printerName
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

    #[Route('/{id}/imprimir', name: 'app_pedidos_imprimir', methods: ['POST'])]
    public function imprimir(int $id, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) {
            return new JsonResponse(['message' => 'Pedido no encontrado'], 404);
        }

        // Formato ESC/POS: encabezado y turno grandes; detalle en tamaño normal.
        $esc = "\x1b";
        $gs = "\x1d";
        $ticket = $esc . "@"; // Inicializar impresora
        $ticket .= $esc . "a" . "\x01"; // Centrado
        $ticket .= $esc . "E" . "\x01" . $gs . "!" . "\x11" . "DONWOK\n";
        $ticket .= $gs . "!" . "\x00" . $esc . "E" . "\x00" . "COMANDA\n";
        $ticket .= $esc . "E" . "\x01" . $gs . "!" . "\x11";
        $ticket .= "TURNO: " . ($pedido->getNumeroTicket() ?? '---') . "\n";
        $ticket .= $gs . "!" . "\x00" . $esc . "E" . "\x00";
        $ticket .= "ORDEN: #" . $pedido->getId() . "\n";
        $ticket .= "FECHA: " . $this->fechaBogota($pedido->getFechaCreacion()) . "\n";
        $ticket .= str_repeat('-', 32) . "\n";
        $ticket .= "DETALLE DEL PEDIDO\n";
        $ticket .= str_repeat('-', 32) . "\n";

        $ticket .= $esc . "a" . "\x00"; // Alinear a la izquierda
        foreach ($pedido->getDetalles() as $detalle) {
            $ticket .= $detalle->getCantidad() . ' x ' . $detalle->getNombreProducto() . "\n";
        }

        $ticket .= str_repeat('-', 32) . "\n";
        $ticket .= "TIPO: " . $pedido->getTipo() . "\n";
        $ticket .= $esc . "a" . "\x01";
        $ticket .= $this->prepararCorte();

        try {
            $this->enviarAImpresora($ticket);
            return new JsonResponse(['message' => 'Ticket enviado a la impresora ' . $this->printerName]);
        } catch (\Throwable $e) {
            error_log('Error imprimiendo ticket: ' . $e->getMessage());
            return new JsonResponse(['message' => 'No se pudo imprimir: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/imprimir-turno', name: 'app_pedidos_imprimir_turno', methods: ['POST'])]
    public function imprimirTurno(int $id, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) {
            return new JsonResponse(['message' => 'Pedido no encontrado'], 404);
        }

        try {
            $this->enviarAImpresora($this->desprendibleTurno($pedido) . $this->prepararCorte());
            return new JsonResponse(['message' => 'Turno enviado a la impresora ' . $this->printerName]);
        } catch (\Throwable $e) {
            error_log('Error imprimiendo turno: ' . $e->getMessage());
            return new JsonResponse(['message' => 'No se pudo imprimir el turno: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/imprimir-comanda-turno', name: 'app_pedidos_imprimir_comanda_turno', methods: ['POST'])]
    public function imprimirComandaYTurno(int $id, Request $request, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) {
            return new JsonResponse(['message' => 'Pedido no encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        // Este nombre solo vive durante esta impresión; nunca se persiste.
        $cliente = trim(substr((string)($data['cliente'] ?? ''), 0, 80));

        $esc = "\x1b";
        $gs = "\x1d";
        $comanda = $esc . "@";
        $comanda .= $esc . "a" . "\x01";
        $comanda .= $esc . "E" . "\x01" . $gs . "!" . "\x11" . "DONWOK\n";
        $comanda .= $gs . "!" . "\x00" . $esc . "E" . "\x00" . "COMANDA\n";
        $comanda .= $esc . "E" . "\x01" . $gs . "!" . "\x11";
        $comanda .= "TURNO: " . ($pedido->getNumeroTicket() ?? '---') . "\n";
        $comanda .= $gs . "!" . "\x00" . $esc . "E" . "\x00";
        $comanda .= "ORDEN: #" . $pedido->getId() . "\n";
        if ($cliente !== '') {
            $comanda .= "CLIENTE: " . $cliente . "\n";
        }
        $comanda .= "FECHA: " . $this->fechaBogota($pedido->getFechaCreacion()) . "\n";
        $comanda .= str_repeat('-', 32) . "\nDETALLE DEL PEDIDO\n" . str_repeat('-', 32) . "\n";
        $comanda .= $esc . "a" . "\x00";

        foreach ($pedido->getDetalles() as $detalle) {
            $comanda .= $detalle->getCantidad() . ' x ' . $detalle->getNombreProducto() . "\n";
        }

        $comanda .= str_repeat('-', 32) . "\nTIPO: " . $pedido->getTipo() . "\n";
        $comanda .= $this->prepararCorte();

        // La comanda se imprime y se corta antes de imprimir el desprendible.
        $secuencia = $comanda . $this->desprendibleTurno($pedido, $cliente) . $this->prepararCorte();

        try {
            $this->enviarAImpresora($secuencia);
            return new JsonResponse(['message' => 'Comanda y turno enviados a la impresora ' . $this->printerName]);
        } catch (\Throwable $e) {
            error_log('Error imprimiendo comanda y turno: ' . $e->getMessage());
            return new JsonResponse(['message' => 'No se pudo imprimir la comanda y el turno: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/imprimir-factura', name: 'app_pedidos_imprimir_factura', methods: ['POST'])]
    public function imprimirFactura(int $id, Request $request, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) {
            return new JsonResponse(['message' => 'Pedido no encontrado'], 404);
        }

        if (!$pedido->getNumeroFactura()) {
            return new JsonResponse(['message' => 'El pedido todavía no está facturado'], 400);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $recibido = max(0, (float)($data['recibido'] ?? 0));
        // Si no se digitó el recibido, el efectivo registrado se considera pago exacto.
        if ($recibido <= 0 && $pedido->getPagoEfectivo() > 0) {
            $recibido = $pedido->getPagoEfectivo();
        }
        $cambio = max(0, $recibido - $pedido->getPagoEfectivo());
        $empresa = $this->em->getRepository(DatosEmpresa::class)->findOneBy([]);

        $ticket = "\x1b@";
        $ticket .= "\x1ba\x01";
        if ($empresa && $empresa->getNombre() !== '') {
            $ticket .= "\x1bE\x01\x1d!\x11" . $empresa->getNombre() . "\n";
        } else {
            $ticket .= "\x1bE\x01\x1d!\x11DON WOK\n";
        }
        $ticket .= "\x1d!\x00\x1bE\x00";
        if ($empresa && $empresa->getNit() !== '') {
            $ticket .= "NIT: " . $empresa->getNit() . "\n";
        }
        $ticket .= "FACTURA DE VENTA\n";
        $ticket .= "\x1bE\x01FACTURA #" . $pedido->getNumeroFactura() . "\n";
        $ticket .= "\x1d!\x00\x1bE\x00";
        $ticket .= "ORDEN: #" . $pedido->getId() . "\n";
        $ticket .= "FECHA: " . $this->fechaBogota($pedido->getFechaCreacion()) . "\n";
        $ticket .= str_repeat('-', 32) . "\n";
        $ticket .= "\x1ba\x00";

        foreach ($pedido->getDetalles() as $detalle) {
            $subtotal = $detalle->getPrecioUnitario() * $detalle->getCantidad();
            $ticket .= $detalle->getCantidad() . ' x ' . $detalle->getNombreProducto() . "\n";
            $ticket .= '   $' . number_format($subtotal, 0, ',', '.') . "\n";
        }

        $ticket .= str_repeat('-', 32) . "\n";
        $ticket .= "TOTAL: $" . number_format($pedido->getTotal(), 0, ',', '.') . "\n";
        $ticket .= "EFECTIVO: $" . number_format($pedido->getPagoEfectivo(), 0, ',', '.') . "\n";
        $ticket .= "NEQUI: $" . number_format($pedido->getPagoNequi(), 0, ',', '.') . "\n";
        if ($pedido->getPagoEfectivo() > 0) {
            $ticket .= "RECIBIDO: $" . number_format($recibido, 0, ',', '.') . "\n";
            $ticket .= "CAMBIO: $" . number_format($cambio, 0, ',', '.') . "\n";
        }
        $ticket .= "TIPO: " . $pedido->getTipo() . "\n";
        $ticket .= "\x1ba\x01";
        if ($empresa && $empresa->getTelefonoDomicilios() !== '') {
            $ticket .= "\x1bE\x01\x1d!\x11PEDIDOS: " . $empresa->getTelefonoDomicilios() . "\n";
            $ticket .= "\x1d!\x00\x1bE\x00";
        }
        if ($empresa && $empresa->getFraseDia()) {
            $ticket .= str_repeat('.', 32) . "\n";
            $ticket .= $empresa->getFraseDia() . "\n";
            $ticket .= str_repeat('.', 32) . "\n";
        }
        $ticket .= "Gracias por preferirnos\n";
        $ticket .= $this->prepararCorte();

        try {
            $this->enviarAImpresora($ticket);
            return new JsonResponse(['message' => 'Factura enviada a la impresora ' . $this->printerName]);
        } catch (\Throwable $e) {
            error_log('Error imprimiendo factura: ' . $e->getMessage());
            return new JsonResponse(['message' => 'No se pudo imprimir la factura: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/pendientes', name: 'app_pedidos_pendientes', methods: ['GET'])]
    public function pendientes(FacturaRepository $repo): JsonResponse
    {
        $pedidos = $repo->findBy(
            ['estado' => [Factura::ESTADO_PENDIENTE, Factura::ESTADO_TERMINADO]],
            ['id' => 'ASC']
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
    public function facturar(int $id, Request $request, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) return new JsonResponse(['message' => 'No encontrado'], 404);
        
        if ($pedido->getEstado() === Factura::ESTADO_FACTURADO) {
            return new JsonResponse(['message' => 'Este pedido ya fue facturado anteriormente']);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $efectivo = (float)($data['efectivo'] ?? 0);
        $nequi = (float)($data['nequi'] ?? 0);

        try {
            $proximoNumero = $repo->getNextInvoiceNumber();
            $pedido->facturar($proximoNumero, $efectivo, $nequi);
            $this->em->flush();
            
            $this->publishUpdate($pedido, 'ORDER_INVOICED');
            return new JsonResponse(['message' => 'Facturado con éxito: ' . $proximoNumero, 'pedido' => $pedido->toArray()]);
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
        $dto = new PedidoRequestDTO(
            $data['items'] ?? [],
            $data['esPago'] ?? false,
            $data['tipo'] ?? 'MESA'
        );
        try {
            $factura = $this->createHandler->handle($dto);
            $this->publishUpdate($factura, 'NEW_ORDER');
            return new JsonResponse(['message' => 'Pedido en cola', 'pedido' => $factura->toArray()], 201);
        } catch (\Exception $e) { return new JsonResponse(['message' => $e->getMessage()], 400); }
    }

    #[Route('/{id}/actualizar-datos', name: 'app_pedidos_actualizar_datos', methods: ['PATCH'])]
    public function actualizarDatos(int $id, Request $request, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) return new JsonResponse(['message' => 'No encontrado'], 404);

        $data = json_decode($request->getContent(), true) ?? [];
        
        try {
            if (isset($data['esPago'])) {
                $pedido->setEsPago((bool)$data['esPago']);
            }
            if (isset($data['tipo'])) {
                $pedido->setTipo((string)$data['tipo']);
            }
            
            $this->em->flush();
            $this->publishUpdate($pedido, 'ORDER_UPDATED');
            
            return new JsonResponse(['message' => 'Datos actualizados', 'pedido' => $pedido->toArray()]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
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

    private function enviarAImpresora(string $contenido): void
    {
        $empresa = $this->em->getRepository(DatosEmpresa::class)->findOneBy([]);
        if ($empresa && !$empresa->isImpresoraActiva()) {
            return;
        }

        $process = new Process(['lp', '-o', 'raw', '-d', $this->printerName], null, null, $contenido);
        $process->setTimeout(15);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'CUPS no pudo imprimir.');
        }
    }

    private function desprendibleTurno(Factura $pedido, string $cliente = ''): string
    {
        $ticket = "\x1b@"
            . "\x1ba\x01"
            . "DESPRENDIBLE CLIENTE\n";

        if ($cliente !== '') {
            $ticket .= $cliente . "\n";
        }

        return $ticket
            . "\x1bE\x01\x1d!\x11"
            . "TURNO: " . ($pedido->getNumeroTicket() ?? '---') . "\n"
            . "\x1d!\x00\x1bE\x00"
            . "ESTADO: " . ($pedido->isEsPago() ? 'PAGADO' : 'PENDIENTE DE PAGO') . "\n"
            . "Gracias por su compra\n";
    }

    private function prepararCorte(): string
    {
        // Avance y corte físico ESC/POS, sin imprimir una línea adicional.
        return "\x1bd\x06" // Avanza 6 líneas para separar el corte del texto.
            . "\x1d\x56\x00"; // Corte total.
    }

    private function fechaBogota(?\DateTimeImmutable $fecha): string
    {
        $zona = new \DateTimeZone('America/Bogota');
        return ($fecha ?? new \DateTimeImmutable('now', $zona))
            ->setTimezone($zona)
            ->format('Y-m-d H:i');
    }
}
