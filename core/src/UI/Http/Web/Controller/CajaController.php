<?php

namespace App\UI\Http\Web\Controller;

use App\Application\Handler\Finanzas\AnularFacturaHandler;
use App\Application\Handler\Finanzas\RealizarCierreCajaHandler;
use App\Application\Handler\Finanzas\RegistrarEgresoHandler;
use App\Application\Handler\Finanzas\RegistrarAbonoHandler;
use App\Domain\Entity\Factura;
use App\Domain\Repository\FacturaRepositoryInterface;
use App\Domain\Repository\MovimientoFinancieroRepositoryInterface;
use App\Domain\Repository\CategoriaFinancieraRepositoryInterface;
use App\Domain\Repository\CuentaFinancieraRepositoryInterface;
use App\Domain\Repository\GastoRepositoryInterface;
use App\Domain\Repository\PrestamoRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caja')]
class CajaController extends AbstractController
{
    public function __construct(
        private RealizarCierreCajaHandler $cierreHandler,
        private AnularFacturaHandler $anularHandler,
        private RegistrarEgresoHandler $egresoHandler,
        private RegistrarAbonoHandler $abonoHandler,
        private GastoRepositoryInterface $gastoRepo,
        private PrestamoRepositoryInterface $prestamoRepo
    ) {}

    #[Route('/egresos-pendientes', name: 'app_caja_egresos_pendientes', methods: ['GET'])]
    public function egresosPendientes(): JsonResponse
    {
        $gastos = $this->gastoRepo->findPendientesCierre();
        $prestamos = $this->prestamoRepo->findPendientesCierre();

        $data = [];
        foreach ($gastos as $g) {
            $data[] = [
                'id' => $g->getId(),
                'tipo' => 'GASTO',
                'concepto' => $g->getConcepto(),
                'monto' => $g->getMonto(),
                'categoria' => $g->getCategoriaFinanciera()->getNombre(),
                'cuenta' => $g->getCuentaFinanciera()->getNombre()
            ];
        }
        foreach ($prestamos as $p) {
            $data[] = [
                'id' => $p->getId(),
                'tipo' => 'PRESTAMO',
                'concepto' => 'Préstamo a ' . $p->getEntidad(),
                'monto' => $p->getMontoTotal(),
                'categoria' => 'Préstamo Otorgado',
                'cuenta' => $p->getCuentaFinanciera()->getNombre()
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/egresos/{id}', name: 'app_caja_egreso_eliminar', methods: ['DELETE'])]
    public function eliminarEgreso(int $id): JsonResponse
    {
        $gasto = $this->gastoRepo->buscarPorId($id);
        if (!$gasto) return new JsonResponse(['message' => 'No encontrado'], 404);
        if ($gasto->isCerrado()) return new JsonResponse(['message' => 'No se puede eliminar un gasto ya cerrado'], 400);

        $this->gastoRepo->eliminar($gasto);
        return new JsonResponse(['message' => 'Egreso eliminado']);
    }

    #[Route('/prestamos-cola/{id}', name: 'app_caja_prestamo_cola_eliminar', methods: ['DELETE'])]
    public function eliminarPrestamoCola(int $id): JsonResponse
    {
        $prestamo = $this->prestamoRepo->buscarPorId($id);
        if (!$prestamo) return new JsonResponse(['message' => 'No encontrado'], 404);
        if ($prestamo->isCerrado()) return new JsonResponse(['message' => 'No se puede eliminar un préstamo ya cerrado'], 400);

        $this->prestamoRepo->eliminar($prestamo);
        return new JsonResponse(['message' => 'Préstamo eliminado de la cola']);
    }

    #[Route('/categorias', name: 'app_caja_categorias', methods: ['GET'])]
    public function categorias(CategoriaFinancieraRepositoryInterface $repo): JsonResponse
    {
        return new JsonResponse(array_map(fn($c) => [
            'id' => $c->getId(),
            'nombre' => $c->getNombre(),
            'tipo' => $c->getTipo()
        ], $repo->listarTodas()));
    }

    #[Route('/cuentas', name: 'app_caja_cuentas', methods: ['GET'])]
    public function cuentas(CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        return new JsonResponse(array_map(fn($c) => [
            'id' => $c->getId(),
            'nombre' => $c->getNombre(),
            'tipo' => $c->getTipo()
        ], $repo->listarTodas()));
    }

    #[Route('/egresos', name: 'app_caja_egresos_registrar', methods: ['POST'])]
    public function registrarEgreso(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        try {
            $res = $this->egresoHandler->handle($data);
            return new JsonResponse(['message' => 'Egreso registrado con éxito', 'detalle' => $res]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/prestamos', name: 'app_caja_prestamos_activos', methods: ['GET'])]
    public function prestamos(PrestamoRepositoryInterface $repo): JsonResponse
    {
        $prestamos = $repo->buscarActivos();
        return new JsonResponse(array_map(fn($p) => [
            'id' => $p->getId(),
            'entidad' => $p->getEntidad(),
            'montoTotal' => $p->getMontoTotal(),
            'saldoPendiente' => $p->getSaldoPendiente(),
            'estado' => $p->getEstado()
        ], $prestamos));
    }

    #[Route('/prestamos/{id}/abono', name: 'app_caja_prestamo_abono', methods: ['POST'])]
    public function registrarAbono(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        try {
            $this->abonoHandler->handle($id, (float)$data['monto'], (int)$data['cuenta_id']);
            return new JsonResponse(['message' => 'Abono registrado con éxito']);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/facturas-emitidas', name: 'app_caja_facturas_emitidas', methods: ['GET'])]
    public function facturasEmitidas(FacturaRepositoryInterface $repo): JsonResponse
    {
        $facturas = $repo->findPendientesCierre();
        return new JsonResponse(array_map(fn($f) => $f->toArray(), $facturas));
    }

    #[Route('/facturas/{id}/anular', name: 'app_caja_factura_anular', methods: ['POST'])]
    public function anular(int $id): JsonResponse
    {
        try {
            $this->anularHandler->handle($id);
            return new JsonResponse(['message' => 'Factura anulada con éxito']);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/cerrar', name: 'app_caja_cerrar', methods: ['POST'])]
    public function cerrarCaja(): JsonResponse
    {
        try {
            $resultado = $this->cierreHandler->handle();
            return new JsonResponse([
                'message' => 'Cierre de caja realizado con éxito',
                'detalle' => $resultado
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/movimientos', name: 'app_caja_movimientos', methods: ['GET'])]
    public function movimientos(MovimientoFinancieroRepositoryInterface $repo): JsonResponse
    {
        $movimientos = $repo->buscarRecientes(50);
        return new JsonResponse(array_map(fn($m) => [
            'id' => $m->getId(),
            'tipo' => $m->getTipoMovimiento(),
            'monto' => $m->getMonto(),
            'fecha' => $m->getFechaMovimiento()->format('Y-m-d H:i:s'),
            'descripcion' => $m->getDescripcion(),
            'categoria' => $m->getCategoriaFinanciera()->getNombre(),
            'cuenta' => $m->getCuentaFinanciera()->getNombre()
        ], $movimientos));
    }
}
