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

    #[Route('/resumen-general', name: 'app_api_informes_resumen', methods: ['GET'])]
    public function resumenGeneral(Request $request): JsonResponse
    {
        $desdeStr = $request->query->get('desde', '-30 days');
        $hastaStr = $request->query->get('hasta', 'now');
        
        $desde = new \DateTime($desdeStr);
        $hasta = new \DateTime($hastaStr);

        $movimientos = $this->movimientoRepo->obtenerTotalesPorPeriodo($desde, $hasta);
        $rentabilidad = $this->detalleRepo->obtenerRentabilidadPorProducto($desde, $hasta);

        $totalIngresos = 0;
        $totalEgresos = 0;
        $totalVentas = 0;
        $totalCostoVenta = array_reduce($rentabilidad, fn($c, $i) => $c + $i['total_costo'], 0);

        foreach ($movimientos as $m) {
            $monto = (float)$m['total'];
            if ($m['tipo'] === 'INGRESO') {
                $totalIngresos += $monto;
                if ($m['categoria'] === 'Ventas Diarias') $totalVentas += $monto;
            } else {
                $totalEgresos += $monto;
            }
        }

        $utilidadTotal = $totalVentas - $totalCostoVenta - ($totalEgresos - 0); // Ajuste: Restamos gastos operativos
        // En realidad la utilidad neta es Ingresos - Egresos - Costos (si los egresos no incluyen el costo de producto)
        // Como nuestras facturas no descuentan inventario en egresos inmediatos, la utilidad es:
        // Ventas - Costo de Productos - Gastos Operativos.
        $gananciaNeta = $totalIngresos - $totalEgresos - $totalCostoVenta; 

        $diff = $desde->diff($hasta);
        $dias = max(1, $diff->days + 1);

        return new JsonResponse([
            'kpis' => [
                'total_ventas' => $totalVentas,
                'total_gastos' => $totalEgresos,
                'utilidad_total' => $totalVentas - $totalCostoVenta - $totalEgresos,
                'promedio_venta_dia' => $totalVentas / $dias,
                'promedio_gasto_dia' => $totalEgresos / $dias,
                'promedio_utilidad_dia' => ($totalVentas - $totalCostoVenta - $totalEgresos) / $dias,
            ]
        ]);
    }

    #[Route('/analitica-mensual-detallada', name: 'app_api_informes_analitica_detallada', methods: ['GET'])]
    public function analiticaMensualDetallada(Request $request): JsonResponse
    {
        $mes = (int)$request->query->get('mes', date('m'));
        $anio = (int)$request->query->get('anio', date('Y'));

        $primerDia = new \DateTime("$anio-$mes-01");
        $ultimoDia = (clone $primerDia)->modify('last day of this month');

        $movimientos = $this->movimientoRepo->obtenerTotalesPorPeriodo($primerDia, $ultimoDia);
        $rentabilidad = $this->detalleRepo->obtenerRentabilidadPorProducto($primerDia, $ultimoDia);
        
        $totalVentaMes = array_reduce($rentabilidad, fn($c, $i) => $c + $i['total_venta'], 0);
        $totalCostoMes = array_reduce($rentabilidad, fn($c, $i) => $c + $i['total_costo'], 0);
        $ratioCosto = $totalVentaMes > 0 ? $totalCostoMes / $totalVentaMes : 0;

        $diario = [];
        $semanal = [
            'Semana 1' => ['ventas' => 0, 'gastos' => 0, 'utilidad' => 0],
            'Semana 2' => ['ventas' => 0, 'gastos' => 0, 'utilidad' => 0],
            'Semana 3' => ['ventas' => 0, 'gastos' => 0, 'utilidad' => 0],
            'Semana 4' => ['ventas' => 0, 'gastos' => 0, 'utilidad' => 0],
        ];

        // Inicializar todos los días del mes
        for ($d = 1; $d <= (int)$ultimoDia->format('d'); $d++) {
            $fecha = "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
            $diario[$fecha] = ['ventas' => 0, 'gastos' => 0, 'utilidad' => 0];
        }

        foreach ($movimientos as $m) {
            $fecha = $m['fecha'];
            $monto = (float)$m['total'];
            $diaMes = (int)(new \DateTime($fecha))->format('d');
            $numSemana = min(4, ceil($diaMes / 7));
            $keySemana = "Semana $numSemana";

            if ($m['tipo'] === 'INGRESO' && $m['categoria'] === 'Ventas Diarias') {
                $diario[$fecha]['ventas'] += $monto;
                $semanal[$keySemana]['ventas'] += $monto;
                
                $costoEstimado = $monto * $ratioCosto;
                $diario[$fecha]['utilidad'] += ($monto - $costoEstimado);
                $semanal[$keySemana]['utilidad'] += ($monto - $costoEstimado);
            } 
            elseif ($m['tipo'] === 'EGRESO') {
                $diario[$fecha]['gastos'] += $monto;
                $diario[$fecha]['utilidad'] -= $monto;
                
                $semanal[$keySemana]['gastos'] += $monto;
                $semanal[$keySemana]['utilidad'] -= $monto;
            }
        }

        return new JsonResponse([
            'diario' => $diario,
            'semanal' => $semanal
        ]);
    }

    #[Route('/detalle-semanal', name: 'app_api_informes_detalle_semanal', methods: ['GET'])]
    public function detalleSemanal(Request $request): JsonResponse
    {
        $fechaInicio = new \DateTime($request->query->get('desde', 'monday this week'));
        $fechaFin = (clone $fechaInicio)->modify('+6 days');

        $movimientos = $this->movimientoRepo->obtenerTotalesPorPeriodo($fechaInicio, $fechaFin);
        $rentabilidad = $this->detalleRepo->obtenerRentabilidadPorProducto($fechaInicio, $fechaFin);
        
        $totalVenta = array_reduce($rentabilidad, fn($c, $i) => $c + $i['total_venta'], 0);
        $totalCosto = array_reduce($rentabilidad, fn($c, $i) => $c + $i['total_costo'], 0);
        $ratioCosto = $totalVenta > 0 ? $totalCosto / $totalVenta : 0;

        $datos = [];
        $diasMap = ['Mon' => 'Lunes', 'Tue' => 'Martes', 'Wed' => 'Miércoles', 'Thu' => 'Jueves', 'Fri' => 'Viernes', 'Sat' => 'Sábado', 'Sun' => 'Domingo'];

        for ($i = 0; $i < 7; $i++) {
            $f = (clone $fechaInicio)->modify("+$i days");
            $fechaKey = $f->format('Y-m-d');
            $datos[$fechaKey] = [
                'dia' => $diasMap[$f->format('D')],
                'ventas' => 0,
                'gastos' => 0,
                'utilidad' => 0
            ];
        }

        foreach ($movimientos as $m) {
            $fecha = $m['fecha'];
            $monto = (float)$m['total'];
            if (!isset($datos[$fecha])) continue;

            if ($m['tipo'] === 'INGRESO' && $m['categoria'] === 'Ventas Diarias') {
                $datos[$fecha]['ventas'] += $monto;
                $datos[$fecha]['utilidad'] += ($monto * (1 - $ratioCosto));
            } elseif ($m['tipo'] === 'EGRESO') {
                $datos[$fecha]['gastos'] += $monto;
                $datos[$fecha]['utilidad'] -= $monto;
            }
        }

        return new JsonResponse(array_values($datos));
    }

    #[Route('/balance-completo', name: 'app_api_informes_balance', methods: ['GET'])]
    public function balanceCompleto(Request $request): JsonResponse
    {
        $desde = new \DateTime($request->query->get('desde', 'first day of this month'));
        $hasta = new \DateTime($request->query->get('hasta', 'now'));
        $cuentas = $this->cuentaRepo->obtenerSaldosActuales();
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

        $rentabilidad = $this->detalleRepo->obtenerRentabilidadPorProducto($desde, $hasta);
        $totalVentasReal = array_reduce($rentabilidad, fn($c, $i) => $c + $i['total_venta'], 0);
        $totalCostoReal = array_reduce($rentabilidad, fn($c, $i) => $c + $i['total_costo'], 0);
        
        $prestamos = $this->prestamoRepo->buscarActivos();
        $totalCartera = array_reduce($prestamos, fn($c, $p) => $c + $p->getSaldoPendiente(), 0);
        $deudas = $this->prestamoRepo->buscarDeudasPendientes();
        $totalPasivos = array_reduce($deudas, fn($c, $p) => $c + $p->getSaldoPendiente(), 0);
        $totalLiquidez = array_reduce($cuentas, fn($c, $i) => $c + $i['saldo'], 0);

        return new JsonResponse([
            'periodo' => ['desde' => $desde->format('Y-m-d'), 'hasta' => $hasta->format('Y-m-d')],
            'liquidez' => ['cuentas' => $cuentas, 'total_efectivo' => $totalLiquidez],
            'cartera' => ['items' => array_map(fn($p) => ['entidad' => $p->getEntidad(), 'saldo' => $p->getSaldoPendiente(), 'fecha' => $p->getFechaCreacion()->format('Y-m-d')], $prestamos), 'total' => $totalCartera],
            'pasivos' => ['items' => array_map(fn($p) => ['entidad' => $p->getEntidad(), 'saldo' => $p->getSaldoPendiente(), 'fecha' => $p->getFechaCreacion()->format('Y-m-d')], $deudas), 'total' => $totalPasivos],
            'patrimonio' => ['neto' => $totalLiquidez + $totalCartera - $totalPasivos],
            'resultados' => [
                'ingresos' => ['total' => $totalIngresos, 'desglose' => $desgloseIngresos],
                'egresos' => ['total' => $totalEgresos, 'desglose' => $desgloseEgresos],
                'utilidad_bruta' => $totalVentasReal - $totalCostoReal,
                'ganancia_neta' => $totalIngresos - $totalEgresos
            ]
        ]);
    }

    #[Route('/movimientos-diarios', name: 'app_api_informes_movimientos', methods: ['GET'])]
    public function movimientosDiarios(Request $request): JsonResponse
    {
        $desde = new \DateTime($request->query->get('desde', '-30 days'));
        $hasta = new \DateTime($request->query->get('hasta', 'now'));
        return new JsonResponse($this->movimientoRepo->obtenerTotalesPorPeriodo($desde, $hasta));
    }

    #[Route('/rentabilidad-productos', name: 'app_api_informes_rentabilidad', methods: ['GET'])]
    public function rentabilidad(Request $request): JsonResponse
    {
        $desde = new \DateTime($request->query->get('desde', '-30 days'));
        $hasta = new \DateTime($request->query->get('hasta', 'now'));
        return new JsonResponse($this->detalleRepo->obtenerRentabilidadPorProducto($desde, $hasta));
    }
}