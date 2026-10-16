<?php

namespace App\UI\Http\Web\Controller;

use App\Domain\Repository\MovimientoFinancieroRepositoryInterface;
use App\Domain\Repository\FacturaDetalleRepositoryInterface;
use App\Domain\Repository\CuentaFinancieraRepositoryInterface;
use App\Domain\Repository\PrestamoRepositoryInterface;
use App\Domain\Repository\InventarioRepositoryInterface;
use App\Domain\Repository\FacturaRepositoryInterface;
use App\Domain\Repository\PagoPrestamoRepositoryInterface;
use App\Domain\Repository\GastoRepositoryInterface;
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
        private PrestamoRepositoryInterface $prestamoRepo,
        private InventarioRepositoryInterface $inventarioRepo,
        private FacturaRepositoryInterface $facturaRepo,
        private PagoPrestamoRepositoryInterface $pagoRepo,
        private GastoRepositoryInterface $gastoRepo
    ) {}

    private function esGastoOperativo(array $movimiento): bool
    {
        return $movimiento['tipo'] === 'EGRESO'
            && $movimiento['categoria'] === 'Gasto';
    }

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
            } elseif ($this->esGastoOperativo($m)) {
                $totalEgresos += $monto;
            }
        }

        // La utilidad del informe es la utilidad neta operativa: ventas - costo
        // de ventas - gastos operativos. Transferencias, inversiones, préstamos,
        // pagos de deuda y ajustes no son gastos operativos.
        $utilidadTotal = $totalVentas - $totalCostoVenta - $totalEgresos;

        $diff = $desde->diff($hasta);
        $dias = max(1, $diff->days + 1);

        return new JsonResponse([
            'kpis' => [
                'total_ventas' => $totalVentas,
                'total_gastos' => $totalEgresos,
                'utilidad_total' => $utilidadTotal,
                'promedio_venta_dia' => $totalVentas / $dias,
                'promedio_gasto_dia' => $totalEgresos / $dias,
                'promedio_utilidad_dia' => $utilidadTotal / $dias,
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
        $costosPorFecha = $this->detalleRepo->obtenerTotalesPorFecha($primerDia, $ultimoDia);

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
            $diario[$fecha] = ['ventas' => 0, 'gastos' => 0, 'costo' => 0, 'utilidad' => 0];
        }

        foreach ($costosPorFecha as $costo) {
            $fecha = $costo['fecha'];
            if (isset($diario[$fecha])) {
                $diario[$fecha]['costo'] = (float)$costo['total_costo'];
            }
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
                
            } elseif ($this->esGastoOperativo($m)) {
                $diario[$fecha]['gastos'] += $monto;
                $semanal[$keySemana]['gastos'] += $monto;
            }
        }

        foreach ($diario as $fecha => &$dia) {
            $dia['utilidad'] = $dia['ventas'] - $dia['costo'] - $dia['gastos'];
            $diaMes = (int)(new \DateTime($fecha))->format('d');
            $semanal['Semana ' . min(4, ceil($diaMes / 7))]['utilidad'] += $dia['utilidad'];
        }
        unset($dia);

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
        $costosPorFecha = $this->detalleRepo->obtenerTotalesPorFecha($fechaInicio, $fechaFin);

        $datos = [];
        $diasMap = ['Mon' => 'Lunes', 'Tue' => 'Martes', 'Wed' => 'Miércoles', 'Thu' => 'Jueves', 'Fri' => 'Viernes', 'Sat' => 'Sábado', 'Sun' => 'Domingo'];

        for ($i = 0; $i < 7; $i++) {
            $f = (clone $fechaInicio)->modify("+$i days");
            $fechaKey = $f->format('Y-m-d');
            $datos[$fechaKey] = [
                'dia' => $diasMap[$f->format('D')],
                'ventas' => 0,
                'gastos' => 0,
                'costo' => 0,
                'utilidad' => 0
            ];
        }

        foreach ($costosPorFecha as $costo) {
            $fecha = $costo['fecha'];
            if (isset($datos[$fecha])) {
                $datos[$fecha]['costo'] = (float)$costo['total_costo'];
            }
        }

        foreach ($movimientos as $m) {
            $fecha = $m['fecha'];
            $monto = (float)$m['total'];
            if (!isset($datos[$fecha])) continue;

            if ($m['tipo'] === 'INGRESO' && $m['categoria'] === 'Ventas Diarias') {
                $datos[$fecha]['ventas'] += $monto;
            } elseif ($this->esGastoOperativo($m)) {
                $datos[$fecha]['gastos'] += $monto;
            }
        }

        foreach ($datos as &$dia) {
            $dia['utilidad'] = $dia['ventas'] - $dia['costo'] - $dia['gastos'];
            unset($dia['costo']);
        }
        unset($dia);

        return new JsonResponse(array_values($datos));
    }

    #[Route('/balance-completo', name: 'app_api_informes_balance', methods: ['GET'])]
    public function balanceCompleto(Request $request): JsonResponse
    {
        $desde = new \DateTime($request->query->get('desde', 'first day of this month'));
        $hasta = new \DateTime($request->query->get('hasta', 'now'));
        $todasLasCuentas = $this->cuentaRepo->obtenerSaldosActuales();
        $cuentas = array_values(array_filter($todasLasCuentas, fn(array $cuenta) => $cuenta['mostrar_en_balance']));
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
        $totalInventario = $this->inventarioRepo->totalDineroInventario();

        // Pendientes de cierre (impacto de arqueo no materializado aún en cuentas financieras)
        $facturasPendientes = $this->facturaRepo->findPendientesCierre();
        $pagosPendientes = $this->pagoRepo->findPendientesCierre();
        $gastosPendientes = $this->gastoRepo->findPendientesCierre();

        // Proyección por cuenta: el arqueo debe sumar/restar cada movimiento en la
        // cuenta donde realmente se hará el cierre (Caja Registradora, Nequi, etc.).
        $pendientesPorCuenta = array_fill_keys(array_map(fn($c) => (string)$c['id'], $todasLasCuentas), 0.0);
        $sumarPendiente = function (int $cuentaId, float $monto) use (&$pendientesPorCuenta): void {
            $clave = (string)$cuentaId;
            if (array_key_exists($clave, $pendientesPorCuenta)) {
                $pendientesPorCuenta[$clave] += $monto;
            }
        };

        $ventasPendientesCierre = 0.0;
        foreach ($facturasPendientes as $factura) {
            $pagos = $factura->getPagos();
            if (!$pagos) {
                $pagos = [];
                $caja = $this->cuentaRepo->buscarPorNombre('Caja Registradora');
                $nequi = $this->cuentaRepo->buscarPorNombre('Cuenta Nequi');
                if ($caja && $factura->getPagoEfectivo() > 0) $pagos[] = ['cuenta_id' => $caja->getId(), 'monto' => $factura->getPagoEfectivo()];
                if ($nequi && $factura->getPagoNequi() > 0) $pagos[] = ['cuenta_id' => $nequi->getId(), 'monto' => $factura->getPagoNequi()];
            }
            foreach ($pagos as $pago) {
                $monto = (float)($pago['monto'] ?? 0);
                $ventasPendientesCierre += $monto;
                $sumarPendiente((int)($pago['cuenta_id'] ?? 0), $monto);
            }
        }

        $egresosPendientesCierre = 0.0;
        foreach ($gastosPendientes as $gasto) {
            $monto = (float)$gasto->getMonto();
            $egresosPendientesCierre += $monto;
            $sumarPendiente((int)$gasto->getCuentaFinanciera()->getId(), -$monto);
        }

        $deudasPagadasPendientesCierre = 0.0; // Pagos de deudas de la empresa (salidas)
        $cobrosCarteraPendientesCierre = 0.0; // Cobros a clientes (ingresos)
        $creditosRecibidosPendientesCierre = 0.0; // Desembolso inicial de deuda recibida (ingresos)

        foreach ($pagosPendientes as $pago) {
            $monto = $pago->getMonto();
            $prestamo = $pago->getPrestamo();
            $cuentaId = (int)$pago->getCuentaFinanciera()->getId();

            if ($prestamo->getTipo() === 'RECIBIDO' && !$pago->esDesembolso()) {
                $deudasPagadasPendientesCierre += $monto;
                $sumarPendiente($cuentaId, -$monto);
            } elseif ($prestamo->getTipo() === 'OTORGADO') {
                $cobrosCarteraPendientesCierre += $monto;
                $sumarPendiente($cuentaId, $monto);
            } elseif ($prestamo->getTipo() === 'RECIBIDO' && $pago->esDesembolso()) {
                $creditosRecibidosPendientesCierre += $monto;
                $sumarPendiente($cuentaId, $monto);
            }
        }

        $netoPendienteCierre =
            $ventasPendientesCierre +
            $cobrosCarteraPendientesCierre +
            $creditosRecibidosPendientesCierre -
            $egresosPendientesCierre -
            $deudasPagadasPendientesCierre;

        $todasLasCuentasProyectadas = array_map(function (array $cuenta) use ($pendientesPorCuenta): array {
            $pendiente = $pendientesPorCuenta[(string)$cuenta['id']] ?? 0.0;
            return array_merge($cuenta, [
                'pendiente_arqueo' => $pendiente,
                'saldo_proyectado' => $cuenta['saldo'] + $pendiente,
            ]);
        }, $todasLasCuentas);
        $cuentasProyectadas = array_values(array_filter(
            $todasLasCuentasProyectadas,
            fn(array $cuenta) => $cuenta['mostrar_en_balance']
        ));
        $cuentasArqueoProyectadas = array_values(array_filter(
            $todasLasCuentasProyectadas,
            fn(array $cuenta) => $cuenta['incluir_en_arqueo']
        ));
        $liquidezProyectada = array_reduce($cuentasProyectadas, fn($c, $i) => $c + $i['saldo_proyectado'], 0.0);
        // Las métricas patrimoniales deben reflejar también los movimientos que
        // todavía no han sido arqueados y que ya están incluidos en la proyección.
        $liquidezProyectadaMenosDeudas = $liquidezProyectada - $totalPasivos;
        $liquidezProyectadaConCarteraMenosDeudas = $liquidezProyectada + $totalCartera - $totalPasivos;
        $patrimonioNeto = $liquidezProyectada + $totalCartera + $totalInventario - $totalPasivos;

        return new JsonResponse([
            'periodo' => ['desde' => $desde->format('Y-m-d'), 'hasta' => $hasta->format('Y-m-d')],
            'liquidez' => [
                'cuentas' => $cuentasProyectadas,
                'cuentas_arqueo' => $cuentasArqueoProyectadas,
                'total_efectivo' => $totalLiquidez,
                'proyectada_con_pendientes' => $liquidezProyectada
            ],
            'pendientes_cierre' => [
                'ventas' => $ventasPendientesCierre,
                'egresos' => $egresosPendientesCierre,
                'deudas_pagadas' => $deudasPagadasPendientesCierre,
                'cobros_cartera' => $cobrosCarteraPendientesCierre,
                'creditos_recibidos' => $creditosRecibidosPendientesCierre,
                'neto' => $netoPendienteCierre
            ],
            'inventario' => ['total' => $totalInventario],
            'cartera' => ['items' => array_map(fn($p) => ['entidad' => $p->getEntidad(), 'saldo' => $p->getSaldoPendiente(), 'fecha' => $p->getFechaCreacion()->format('Y-m-d')], $prestamos), 'total' => $totalCartera],
            'pasivos' => ['items' => array_map(fn($p) => ['entidad' => $p->getEntidad(), 'saldo' => $p->getSaldoPendiente(), 'fecha' => $p->getFechaCreacion()->format('Y-m-d')], $deudas), 'total' => $totalPasivos],
            'patrimonio' => [
                'liquidez_menos_deudas' => $liquidezProyectadaMenosDeudas,
                'liquidez_cartera_menos_deudas' => $liquidezProyectadaConCarteraMenosDeudas,
                'neto' => $patrimonioNeto
            ],
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
