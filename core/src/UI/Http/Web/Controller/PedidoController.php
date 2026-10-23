<?php

namespace App\UI\Http\Web\Controller;

use App\Application\DTO\Request\PedidoRequestDTO;
use App\Application\Handler\Pedido\CreatePedidoHandler;
use App\Application\Handler\Pedido\EliminarPedidoHandler;
use App\Domain\Entity\DatosEmpresa;
use App\Infrastructure\Doctrine\Repository\FacturaRepository;
use App\Domain\Entity\Factura;
use App\Domain\Repository\CuentaFinancieraRepositoryInterface;
use App\Domain\Repository\ProductoRepositoryInterface;
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
        private string $printerName,
        private CuentaFinancieraRepositoryInterface $cuentaRepo,
        private ProductoRepositoryInterface $productoRepo
    ) {}

    #[Route('/stats', name: 'app_pedidos_stats', methods: ['GET'])]
    public function stats(FacturaRepository $repo): JsonResponse
    {
        $pendientes = $repo->count(['estado' => Factura::ESTADO_PENDIENTE]);
        $empaque = $repo->count(['estado' => Factura::ESTADO_EMPAQUE]);
        $terminados = $repo->count(['estado' => Factura::ESTADO_TERMINADO]);

        return new JsonResponse([
            'pendientes' => $pendientes,
            'empaque' => $empaque,
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

        // Formato ESC/POS: número de orden destacado y detalle en tamaño normal.
        $esc = "\x1b";
        $gs = "\x1d";
        $ticket = $esc . "@"; // Inicializar impresora
        $ticket .= $esc . "a" . "\x01"; // Centrado
        $ticket .= $esc . "E" . "\x01" . $gs . "!" . "\x11" . "DONWOK\n";
        $ticket .= $gs . "!" . "\x00" . $esc . "E" . "\x00" . "COMANDA\n";
        $ticket .= $esc . "E" . "\x01" . $gs . "!" . "\x22";
        $ticket .= "ORDEN #" . $pedido->getId() . "\n";
        $ticket .= $gs . "!" . "\x00" . $esc . "E" . "\x00";
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

    #[Route('/{id}/imprimir-comanda', name: 'app_pedidos_imprimir_comanda', methods: ['POST'])]
    public function imprimirComanda(int $id, Request $request, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) {
            return new JsonResponse(['message' => 'Pedido no encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $cliente = $pedido->getClienteNombre() ?: '';
        $domicilio = $pedido->getDomicilioDescripcion() ?: '';

        $esc = "\x1b";
        $gs = "\x1d";
        $comanda = $esc . "@";
        $comanda .= $esc . "a" . "\x01";
        $comanda .= $esc . "E" . "\x01" . $gs . "!" . "\x11" . "DONWOK\n";
        $comanda .= $gs . "!" . "\x00" . $esc . "E" . "\x00" . "COMANDA\n";
        $comanda .= $esc . "E" . "\x01" . $gs . "!" . "\x22";
        $comanda .= "ORDEN #" . $pedido->getId() . "\n";
        $comanda .= $gs . "!" . "\x00" . $esc . "E" . "\x00";
        $comanda .= "FECHA: " . $this->fechaBogota($pedido->getFechaCreacion()) . "\n";
        $comanda .= str_repeat('-', 32) . "\nDETALLE DEL PEDIDO\n" . str_repeat('-', 32) . "\n";
        $comanda .= $esc . "a" . "\x00";

        foreach ($pedido->getDetalles() as $detalle) {
            $comanda .= $detalle->getCantidad() . ' x ' . $detalle->getNombreProducto() . "\n";
        }

        $comanda .= str_repeat('-', 32) . "\nTIPO: " . $pedido->getTipo() . "\n";
        $comanda .= $this->prepararCorte();

        // La comanda se imprime y se corta antes de imprimir el desprendible del cliente.
        $secuencia = $comanda . $this->desprendibleCliente($pedido, $cliente, $domicilio) . $this->prepararCorte();

        try {
            $this->enviarAImpresora($secuencia);
            return new JsonResponse(['message' => 'Comanda y desprendible enviados a la impresora ' . $this->printerName]);
        } catch (\Throwable $e) {
            error_log('Error imprimiendo comanda y desprendible: ' . $e->getMessage());
            return new JsonResponse(['message' => 'No se pudo imprimir la comanda y el desprendible: ' . $e->getMessage()], 500);
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
        // El cajón está conectado al puerto RJ de la impresora. Solo se abre
        // cuando la factura tiene un componente pagado en efectivo.
        if ($pedido->getPagoEfectivo() > 0) {
            $ticket .= $this->abrirCajon();
        }

        try {
            // La decisión de imprimir se toma explícitamente desde Gestión;
            // no debe quedar bloqueada por el interruptor general de empresa.
            $this->enviarAImpresora($ticket, true);
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
            ['estado' => [Factura::ESTADO_PENDIENTE, Factura::ESTADO_EMPAQUE, Factura::ESTADO_TERMINADO]],
            ['id' => 'ASC']
        );
        return new JsonResponse(array_map(fn($p) => $p->toArray(), $pedidos));
    }

    #[Route('/{id}', name: 'app_pedidos_obtener', methods: ['GET'])]
    public function obtener(int $id, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) return new JsonResponse(['message' => 'Pedido no encontrado'], 404);
        return new JsonResponse($pedido->toArray());
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

    #[Route('/{id}/empaque', name: 'app_pedidos_empaque', methods: ['POST'])]
    public function empaque(int $id, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) return new JsonResponse(['message' => 'Pedido no encontrado'], 404);

        try {
            $pedido->marcarComoEmpaque();
            $this->em->flush();
            $this->publishUpdate($pedido, 'ORDER_PACKING');
            return new JsonResponse(['message' => 'Pedido enviado a empaque', 'pedido' => $pedido->toArray()]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/avisar-listo', name: 'app_pedidos_avisar_listo', methods: ['POST'])]
    public function avisarListo(int $id, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) return new JsonResponse(['message' => 'Pedido no encontrado'], 404);
        if ($pedido->getEstado() !== Factura::ESTADO_TERMINADO) {
            return new JsonResponse(['message' => 'Solo se puede avisar un pedido terminado.'], 400);
        }

        // Este aviso no cambia el pedido ni genera movimientos contables.
        $this->publishUpdate($pedido, 'ORDER_READY_REMINDER');

        return new JsonResponse([
            'message' => 'Aviso enviado a Gestión y Ventas',
            'pedido' => $pedido->toArray(),
        ]);
    }

    #[Route('/{id}/entregar', name: 'app_pedidos_entregar', methods: ['POST'])]
    public function entregar(int $id, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) return new JsonResponse(['message' => 'Pedido no encontrado'], 404);

        try {
            $pedido->marcarComoEntregado();
            $this->em->flush();
            $this->publishUpdate($pedido, 'ORDER_DELIVERED');

            return new JsonResponse(['message' => 'Pedido entregado', 'pedido' => $pedido->toArray()]);
        } catch (\Exception $e) {
            error_log('Error entregando pedido: ' . $e->getMessage());
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/facturar', name: 'app_pedidos_facturar', methods: ['POST'])]
    public function facturar(int $id, Request $request, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) return new JsonResponse(['message' => 'No encontrado'], 404);
        
        if ($pedido->getNumeroFactura() !== null) {
            return new JsonResponse(['message' => 'Este pedido ya fue facturado anteriormente']);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        try {
            $proximoNumero = $repo->getNextInvoiceNumber();
            if (isset($data['pagos']) && is_array($data['pagos'])) {
                $pagos = [];
                foreach ($data['pagos'] as $pago) {
                    $cuentaId = (int)($pago['cuenta_id'] ?? 0);
                    $monto = (float)($pago['monto'] ?? 0);
                    $cuenta = $this->cuentaRepo->buscarPorId($cuentaId);
                    if (!$cuenta || !$cuenta->estaActiva() || !$cuenta->usarEnFacturacion() || $monto <= 0) {
                        throw new \InvalidArgumentException('La cuenta seleccionada no está disponible para facturación.');
                    }
                    $pagos[] = ['cuenta_id' => $cuenta->getId(), 'monto' => round($monto, 2), 'tipo' => $cuenta->getTipo()];
                }
                if (!$pagos) throw new \InvalidArgumentException('Distribuye el total entre al menos una cuenta.');
                $pedido->facturarConPagos($proximoNumero, $pagos);
            } else {
                $pedido->facturar($proximoNumero, (float)($data['efectivo'] ?? 0), (float)($data['nequi'] ?? 0));
            }
            $this->em->flush();
            
            // La factura no saca el pedido de producción; solo actualiza la fila.
            $this->publishUpdate($pedido, 'ORDER_UPDATED');
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
            $factura->setDatosCliente($data['cliente_nombre'] ?? null, $data['domicilio_descripcion'] ?? null);
            $this->em->flush();
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
            if ($pedido->getNumeroFactura() !== null && (isset($data['tipo']) || isset($data['esPago']))) {
                throw new \InvalidArgumentException('No se puede modificar un pedido que ya fue facturado.');
            }
            if (isset($data['esPago'])) {
                $pedido->setEsPago((bool)$data['esPago']);
            }
            if (isset($data['tipo'])) {
                $pedido->setTipo((string)$data['tipo']);
            }
            if (array_key_exists('cliente_nombre', $data) || array_key_exists('domicilio_descripcion', $data)) {
                $pedido->setDatosCliente($data['cliente_nombre'] ?? $pedido->getClienteNombre(), $data['domicilio_descripcion'] ?? $pedido->getDomicilioDescripcion());
            }
            
            $this->em->flush();
            $this->publishUpdate($pedido, 'ORDER_UPDATED');
            
            return new JsonResponse(['message' => 'Datos actualizados', 'pedido' => $pedido->toArray()]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/items', name: 'app_pedidos_actualizar_items', methods: ['PATCH'])]
    public function actualizarItems(int $id, Request $request, FacturaRepository $repo): JsonResponse
    {
        $pedido = $repo->find($id);
        if (!$pedido) return new JsonResponse(['message' => 'No encontrado'], 404);
        if ($pedido->getNumeroFactura() !== null) {
            return new JsonResponse(['message' => 'No se puede modificar un pedido que ya fue facturado.'], 400);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $items = $data['items'] ?? null;
        if (!is_array($items) || count($items) === 0) {
            return new JsonResponse(['message' => 'El pedido debe tener al menos un producto.'], 400);
        }

        try {
            $normalizados = [];
            foreach ($items as $item) {
                if (($item['temporal'] ?? false) === true) {
                    $nombre = trim((string)($item['nombre'] ?? ''));
                    $precio = (float)($item['precioActual'] ?? 0);
                    $costo = (float)($item['costoActual'] ?? 0);
                    $cantidad = (int)($item['cantidad'] ?? 0);
                    if ($nombre === '' || $precio <= 0 || $costo < 0 || $cantidad < 1) {
                        throw new \InvalidArgumentException('Los datos del producto temporal no son válidos.');
                    }
                    $normalizados[] = ['productoId' => null, 'nombre' => $nombre, 'precio' => $precio, 'costo' => $costo, 'cantidad' => $cantidad];
                    continue;
                }
                $productoId = (int)($item['producto_id'] ?? 0);
                $cantidad = (int)($item['cantidad'] ?? 0);
                if ($productoId < 1 || $cantidad < 1) {
                    throw new \InvalidArgumentException('Cada producto debe tener una cantidad válida.');
                }
                $producto = $this->productoRepo->buscarPorId($productoId);
                if (!$producto || !$producto->isActivo()) {
                    throw new \InvalidArgumentException('Uno de los productos no está disponible.');
                }
                $normalizados[] = [
                    'productoId' => $producto->getId(),
                    'nombre' => $producto->getNombre(),
                    'precio' => $producto->getPrecioActual(),
                    'costo' => $producto->getCostoActual(),
                    'cantidad' => $cantidad,
                ];
            }

            $pedido->reemplazarItems($normalizados, $this->getUser()?->getUserIdentifier() ?? 'sistema');
            $this->em->flush();
            $this->publishUpdate($pedido, 'ORDER_UPDATED');
            return new JsonResponse(['message' => 'Productos actualizados', 'pedido' => $pedido->toArray()]);
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

    private function enviarAImpresora(string $contenido, bool $forzar = false): void
    {
        $empresa = $this->em->getRepository(DatosEmpresa::class)->findOneBy([]);
        if (!$forzar && $empresa && !$empresa->isImpresoraActiva()) {
            return;
        }

        $process = new Process(['lp', '-o', 'raw', '-d', $this->printerName], null, null, $contenido);
        $process->setTimeout(15);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'CUPS no pudo imprimir.');
        }
    }

    private function desprendibleCliente(Factura $pedido, string $cliente = '', string $domicilio = ''): string
    {
        $ticket = "\x1b@"
            . "\x1ba\x01"
            . "DESPRENDIBLE CLIENTE\n"
            . "\x1bE\x01\x1d!\x11"
            . "ORDEN #" . $pedido->getId() . "\n"
            . "\x1d!\x00\x1bE\x00";

        if ($cliente !== '') {
            $ticket .= $cliente . "\n";
        }

        if ($pedido->getTipo() === 'DOMICILIO' && $domicilio !== '') {
            $ticket .= "DOMICILIO:\n" . $domicilio . "\n";
        }

        $ticket .= str_repeat('-', 32) . "\nDETALLE DEL PEDIDO\n" . str_repeat('-', 32) . "\n";
        $ticket .= "\x1ba\x00";

        foreach ($pedido->getDetalles() as $detalle) {
            $subtotal = $detalle->getPrecioUnitario() * $detalle->getCantidad();
            $ticket .= $detalle->getCantidad() . ' x ' . $detalle->getNombreProducto() . "\n";
            $ticket .= '   $' . number_format($detalle->getPrecioUnitario(), 0, ',', '.')
                . ' c/u  $' . number_format($subtotal, 0, ',', '.') . "\n";
        }

        return $ticket
            . str_repeat('-', 32) . "\n"
            . "TOTAL: $" . number_format($pedido->getTotal(), 0, ',', '.') . "\n"
            . "TIPO: " . $pedido->getTipo() . "\n"
            . "ESTADO: " . ($pedido->isEsPago() ? 'PAGADO' : 'PENDIENTE DE PAGO') . "\n"
            . "Gracias por su compra\n";
    }

    private function prepararCorte(): string
    {
        // Avance y corte físico ESC/POS, sin imprimir una línea adicional.
        return "\x1bd\x06" // Avanza 6 líneas para separar el corte del texto.
            . "\x1d\x56\x00"; // Corte total.
    }

    private function abrirCajon(): string
    {
        // ESC/POS: ESC p m t1 t2. Pin 2, 50 ms activo y 500 ms de pausa.
        return "\x1b\x70\x00\x19\xfa";
    }

    private function fechaBogota(?\DateTimeImmutable $fecha): string
    {
        $zona = new \DateTimeZone('America/Bogota');
        return ($fecha ?? new \DateTimeImmutable('now', $zona))
            ->setTimezone($zona)
            ->format('Y-m-d H:i');
    }
}
