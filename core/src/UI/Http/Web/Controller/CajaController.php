<?php

namespace App\UI\Http\Web\Controller;

use App\Application\Handler\Finanzas\AnularFacturaHandler;
use App\Application\Handler\Finanzas\RealizarCierreCajaHandler;
use App\Domain\Entity\Factura;
use App\Domain\Repository\FacturaRepositoryInterface;
use App\Infrastructure\Doctrine\Repository\MovimientoFinancieroRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caja')]
class CajaController extends AbstractController
{
    public function __construct(
        private RealizarCierreCajaHandler $cierreHandler,
        private AnularFacturaHandler $anularHandler
    ) {}

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
    public function movimientos(MovimientoFinancieroRepository $repo): JsonResponse
    {
        $movimientos = $repo->findBy([], ['fechaMovimiento' => 'DESC'], 50);
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
