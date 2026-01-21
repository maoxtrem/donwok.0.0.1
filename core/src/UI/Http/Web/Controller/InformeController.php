<?php

namespace App\UI\Http\Web\Controller;

use App\Domain\Repository\MovimientoFinancieroRepositoryInterface;
use App\Domain\Repository\FacturaDetalleRepositoryInterface;
use App\Domain\Repository\CuentaFinancieraRepositoryInterface;
use App\Domain\Repository\PrestamoRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/informes')]
class InformeController extends AbstractController
{
    public function __construct(
        private MovimientoFinancieroRepositoryInterface $movimientoRepo,
        private FacturaDetalleRepositoryInterface $detalleRepo,
        private CuentaFinancieraRepositoryInterface $cuentaRepo,
        private PrestamoRepositoryInterface $prestamoRepo
    ) {}

    #[Route('/balance-completo', name: 'app_api_informes_balance', methods: ['GET'])]
    public function balanceCompleto(Request $request): JsonResponse
    {
        $desde = new \DateTime($request->query->get('desde', 'first day of this month'));
        $hasta = new \DateTime($request->query->get('hasta', 'now'));

        // 1. Liquidez Actual (No depende de las fechas del filtro, es el HOY)
        $cuentas = $this->cuentaRepo->obtenerSaldosActuales();

        // 2. Movimientos desglosados por categoría en el periodo
        $movimientos = $this->movimientoRepo->obtenerTotalesPorPeriodo($desde, $hasta);
        
        $desgloseIngresos = [];
        $desgloseEgresos = [];
        $totalIngresos = 0;
        $totalEgresos = 0;

        foreach ($movimientos as $m) {
            $monto = (float)$m['total'];
            $cat = $m['categoria'];
            if ($m['tipo'] === 'INGRESO') {
                $totalIngresos += $monto;
                $desgloseIngresos[$cat] = ($desgloseIngresos[$cat] ?? 0) + $monto;
            } else {
                $totalEgresos += $monto;
                $desgloseEgresos[$cat] = ($desgloseEgresos[$cat] ?? 0) + $monto;
            }
        }

        // 3. Rentabilidad Detallada
        $rentabilidad = $this->detalleRepo->obtenerRentabilidadPorProducto($desde, $hasta);
        $totalVentasReal = array_reduce($rentabilidad, fn($c, $i) => $c + $i['total_venta'], 0);
        $totalCostoReal = array_reduce($rentabilidad, fn($c, $i) => $c + $i['total_costo'], 0);
        $utilidadBruta = $totalVentasReal - $totalCostoReal;

        // 4. Cartera (Préstamos Pendientes - OTORGADOS)
        $prestamos = $this->prestamoRepo->buscarActivos();
        $totalCartera = array_reduce($prestamos, fn($c, $p) => $c + $p->getSaldoPendiente(), 0);

        // 5. Pasivos (Deudas de la Empresa - RECIBIDOS)
        $deudas = $this->prestamoRepo->buscarDeudasPendientes();
        $totalPasivos = array_reduce($deudas, fn($c, $p) => $c + $p->getSaldoPendiente(), 0);

        $totalLiquidez = array_reduce($cuentas, fn($c, $i) => $c + $i['saldo'], 0);

        return new JsonResponse([
            'periodo' => [
                'desde' => $desde->format('Y-m-d'),
                'hasta' => $hasta->format('Y-m-d')
            ],
            'liquidez' => [
                'cuentas' => $cuentas,
                'total_efectivo' => $totalLiquidez
            ],
            'cartera' => [
                'items' => array_map(fn($p) => [
                    'entidad' => $p->getEntidad(),
                    'saldo' => $p->getSaldoPendiente(),
                    'fecha' => $p->getFechaCreacion()->format('Y-m-d')
                ], $prestamos),
                'total' => $totalCartera
            ],
            'pasivos' => [
                'items' => array_map(fn($p) => [
                    'entidad' => $p->getEntidad(),
                    'saldo' => $p->getSaldoPendiente(),
                    'fecha' => $p->getFechaCreacion()->format('Y-m-d')
                ], $deudas),
                'total' => $totalPasivos
            ],
            'patrimonio' => [
                'neto' => $totalLiquidez + $totalCartera - $totalPasivos
            ],
            'resultados' => [
                'ingresos' => [
                    'total' => $totalIngresos,
                    'desglose' => $desgloseIngresos
                ],
                'egresos' => [
                    'total' => $totalEgresos,
                    'desglose' => $desgloseEgresos
                ],
                'utilidad_bruta' => $utilidadBruta,
                'ventas_netas' => $totalVentasReal,
                'costos_netos' => $totalCostoReal,
                'ganancia_neta' => $totalIngresos - $totalEgresos
            ]
        ]);
    }

    #[Route('/movimientos-diarios', name: 'app_api_informes_movimientos', methods: ['GET'])]
    public function movimientosDiarios(Request $request): JsonResponse
    {
        $desde = new \DateTime($request->query->get('desde', '-30 days'));
        $hasta = new \DateTime($request->query->get('hasta', 'now'));

        $data = $this->movimientoRepo->obtenerTotalesPorPeriodo($desde, $hasta);
        return new JsonResponse($data);
    }

    #[Route('/rentabilidad-productos', name: 'app_api_informes_rentabilidad', methods: ['GET'])]
    public function rentabilidad(Request $request): JsonResponse
    {
        $desde = new \DateTime($request->query->get('desde', '-30 days'));
        $hasta = new \DateTime($request->query->get('hasta', 'now'));

        $data = $this->detalleRepo->obtenerRentabilidadPorProducto($desde, $hasta);
        return new JsonResponse($data);
    }

    #[Route('/resumen-general', name: 'app_api_informes_resumen', methods: ['GET'])]
    public function resumenGeneral(Request $request): JsonResponse
    {
        $desde = new \DateTime($request->query->get('desde', '-30 days'));
        $hasta = new \DateTime($request->query->get('hasta', 'now'));

        // Totales del periodo actual
        $movimientos = $this->movimientoRepo->obtenerTotalesPorPeriodo($desde, $hasta);
        
        $totalIngresos = 0;
        $totalEgresos = 0;
        $totalInversiones = 0;
        $totalVentas = 0;

        foreach ($movimientos as $m) {
            $monto = (float)$m['total'];
            if ($m['tipo'] === 'INGRESO') {
                $totalIngresos += $monto;
                if ($m['categoria'] === 'Ventas Diarias') $totalVentas += $monto;
            } else {
                $totalEgresos += $monto;
                if ($m['categoria'] === 'Inversiones') $totalInversiones += $monto;
            }
        }

        // Calculamos rentabilidad bruta desde detalles
        $rentabilidad = $this->detalleRepo->obtenerRentabilidadPorProducto($desde, $hasta);
        $utilidadBruta = array_reduce($rentabilidad, fn($carry, $item) => $carry + $item['ganancia'], 0);

        // Periodo anterior para crecimiento
        $intervalo = $desde->diff($hasta);
        $desdeAnt = (clone $desde)->sub($intervalo);
        $hastaAnt = (clone $desde)->modify('-1 second');
        
        $movimientosAnt = $this->movimientoRepo->obtenerTotalesPorPeriodo($desdeAnt, $hastaAnt);
        $totalIngresosAnt = array_reduce($movimientosAnt, fn($c, $i) => $c + ($i['tipo'] === 'INGRESO' ? $i['total'] : 0), 0);
        
        $crecimiento = 0;
        if ($totalIngresosAnt > 0) {
            $crecimiento = (($totalIngresos - $totalIngresosAnt) / $totalIngresosAnt) * 100;
        }

        return new JsonResponse([
            'kpis' => [
                'total_ingresos' => $totalIngresos,
                'total_egresos' => $totalEgresos,
                'total_inversiones' => $totalInversiones,
                'total_ventas' => $totalVentas,
                'utilidad_bruta' => $utilidadBruta,
                'balance_neto' => $totalIngresos - $totalEgresos,
                'crecimiento_porcentaje' => round($crecimiento, 2),
                'promedio_venta_diaria' => count($movimientos) > 0 ? $totalVentas / max(1, $intervalo->days) : 0
            ]
        ]);
    }
}
