<?php

namespace App\Application\Handler\Finanzas;

use App\Domain\Entity\Factura;
use App\Domain\Entity\DatosEmpresa;
use App\Domain\Entity\MovimientoFinanciero;
use App\Domain\Entity\Prestamo;
use App\Domain\Entity\CuentaFinanciera;
use App\Domain\Entity\TransferenciaArqueo;
use App\Application\Service\CalcularDeduccionesCierre;
use App\Application\Service\DeduccionesCierreService;
use App\Domain\Repository\FacturaRepositoryInterface;
use App\Domain\Repository\CategoriaFinancieraRepositoryInterface;
use App\Domain\Repository\CuentaFinancieraRepositoryInterface;
use App\Domain\Repository\GastoRepositoryInterface;
use App\Domain\Repository\PrestamoRepositoryInterface;
use App\Domain\Repository\PagoPrestamoRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class RealizarCierreCajaHandler
{
    public function __construct(
        private FacturaRepositoryInterface $facturaRepo,
        private GastoRepositoryInterface $gastoRepo,
        private PrestamoRepositoryInterface $prestamoRepo,
        private PagoPrestamoRepositoryInterface $pagoRepo,
        private CategoriaFinancieraRepositoryInterface $categoriaRepo,
        private CuentaFinancieraRepositoryInterface $cuentaRepo,
        private EntityManagerInterface $em,
        private CalcularDeduccionesCierre $deduccionesCalculator,
        private DeduccionesCierreService $deduccionesCierreService
    ) {}

    public function handle(array $saldosContados, string $auditadoPor = 'sistema'): array
    {
        $facturas = $this->facturaRepo->findPendientesCierre();
        $gastos = $this->gastoRepo->findPendientesCierre();
        $abonos = $this->pagoRepo->findPendientesCierre();
        
        if (empty($facturas) && empty($gastos) && empty($abonos)) {
            throw new \Exception("No hay movimientos pendientes de cierre.");
        }

        $movimientosGenerados = 0;

        // --- 1. PROCESAR INGRESOS POR VENTAS (FACTURAS) ---
        $ventasPorCuenta = [];
        $variacionPorCuenta = [];
        $totalVentaEfectivo = 0;
        $totalVentaNequi = 0;
        $catVentas = $this->categoriaRepo->buscarPorNombre('Ventas Diarias');
        foreach ($facturas as $f) {
            $pagos = $f->getPagos();
            if (!$pagos) {
                $pagos = [];
                if ($f->getPagoEfectivo() > 0) $pagos[] = ['cuenta_id' => $this->cuentaRepo->buscarPorNombre('Caja Registradora')?->getId(), 'monto' => $f->getPagoEfectivo(), 'tipo' => 'CAJA'];
                if ($f->getPagoNequi() > 0) $pagos[] = ['cuenta_id' => $this->cuentaRepo->buscarPorNombre('Cuenta Nequi')?->getId(), 'monto' => $f->getPagoNequi(), 'tipo' => 'BANCO'];
            }
            foreach ($pagos as $pago) {
                $cuenta = $this->cuentaRepo->buscarPorId((int)($pago['cuenta_id'] ?? 0));
                if (!$cuenta || (float)$pago['monto'] <= 0) continue;
                $monto = (float)$pago['monto'];
                $ventasPorCuenta[$cuenta->getId()] = ($ventasPorCuenta[$cuenta->getId()] ?? 0) + $monto;
                $variacionPorCuenta[$cuenta->getId()] = ($variacionPorCuenta[$cuenta->getId()] ?? 0) + $monto;
                if ($cuenta->incluirEnArqueo()) {
                    if ($cuenta->getTipo() === 'CAJA') $totalVentaEfectivo += $monto;
                    else $totalVentaNequi += $monto;
                }
            }
        }
        foreach ($ventasPorCuenta as $cuentaId => $monto) {
            $cuenta = $this->cuentaRepo->buscarPorId((int)$cuentaId);
            $this->em->persist(new MovimientoFinanciero('INGRESO', $monto, $catVentas, $cuenta, 'venta', null, 'Cierre: Ventas en ' . $cuenta->getNombre()));
            $movimientosGenerados++;
        }

        // --- 2. PROCESAR ABONOS Y DESEMBOLSOS RECIBIDOS ---
        $catCobro = $this->categoriaRepo->buscarPorNombre('Cobro de Préstamo');
        $catIngresoCredito = $this->categoriaRepo->buscarPorNombre('Crédito');
        $catGastoDeuda = $this->categoriaRepo->buscarPorNombre('Pago de Obligaciones');

        $esperadoEfectivo = $totalVentaEfectivo;
        $esperadoNequi = $totalVentaNequi;

        foreach ($abonos as $a) {
            $esCartera = ($a->getPrestamo()->getTipo() === 'OTORGADO');
            
            if ($esCartera) {
                // Cobro a un cliente (INGRESO)
                $tipoMov = 'INGRESO';
                $catMov = $catCobro;
            } else {
                // Deuda de la empresa
                if ($a->esDesembolso()) {
                    // Es el dinero que entró inicialmente (INGRESO)
                    $tipoMov = 'INGRESO';
                    $catMov = $catIngresoCredito;
                } else {
                    // Es un pago que estamos haciendo a la deuda (EGRESO)
                    $tipoMov = 'EGRESO';
                    $catMov = $catGastoDeuda;
                }
            }

            $this->em->persist(new MovimientoFinanciero(
                $tipoMov, 
                $a->getMonto(), 
                $catMov, 
                $a->getCuentaFinanciera(), 
                'prestamo', 
                $a->getPrestamo()->getId(), 
                ($tipoMov === 'INGRESO' ? "Cierre: Entrada de " : "Cierre: Pago a ") . $a->getPrestamo()->getEntidad()
            ));
            $a->cerrar();
            $movimientosGenerados++;

            $factor = $tipoMov === 'INGRESO' ? 1 : -1;
            if ($a->getCuentaFinanciera()->incluirEnArqueo()) {
                if ($a->getCuentaFinanciera()->getTipo() === 'CAJA') {
                    $esperadoEfectivo += $factor * $a->getMonto();
                } else {
                    $esperadoNequi += $factor * $a->getMonto();
                }
            }
            $cuentaId = $a->getCuentaFinanciera()->getId();
            $variacionPorCuenta[$cuentaId] = ($variacionPorCuenta[$cuentaId] ?? 0) + $factor * $a->getMonto();
        }

        // --- 3. PROCESAR EGRESOS (GASTOS, INVERSIONES Y DESEMBOLSOS OTORGADOS) ---
        foreach ($gastos as $g) {
            $this->em->persist(new MovimientoFinanciero('EGRESO', $g->getMonto(), $g->getCategoriaFinanciera(), $g->getCuentaFinanciera(), 'gasto', $g->getId(), "Cierre: {$g->getConcepto()}"));
            $g->cerrar();
            $movimientosGenerados++;

            if ($g->getCuentaFinanciera()->incluirEnArqueo()) {
                if ($g->getCuentaFinanciera()->getTipo() === 'CAJA') {
                    $esperadoEfectivo -= $g->getMonto();
                } else {
                    $esperadoNequi -= $g->getMonto();
                }
            }
            $cuentaId = $g->getCuentaFinanciera()->getId();
            $variacionPorCuenta[$cuentaId] = ($variacionPorCuenta[$cuentaId] ?? 0) - $g->getMonto();
        }

        // Cerrar facturas
        foreach ($facturas as $f) { $f->cerrar(); }

        $empresa = $this->em->getRepository(DatosEmpresa::class)->findOneBy([]);
        $deducciones = $empresa ? $this->deduccionesCierreService->obtenerAplicables($empresa) : [];
        $totalVentas = array_reduce($facturas, fn(float $total, Factura $factura) => $total + $factura->getTotal(), 0.0);
        $detalleDeducciones = $this->deduccionesCalculator->calcular($totalVentas, $deducciones);

        $categoriaDeduccion = $this->categoriaRepo->buscarPorNombre('Gasto Pendiente');
        $cuentaDeduccion = $this->cuentaRepo->buscarPorNombre('Caja Registradora');
        $deudasGeneradas = 0;
        $detallesPorId = [];
        foreach ($detalleDeducciones as $detalle) {
            $detallesPorId[$detalle['id']] = $detalle;
        }
        foreach ($deducciones as $deduccion) {
            $detalle = $detallesPorId[$deduccion->getId()] ?? null;
            if (!$detalle || $detalle['monto'] <= 0) {
                continue;
            }
            if (!$categoriaDeduccion || !$cuentaDeduccion) {
                throw new \RuntimeException('No existe la categoría o cuenta necesaria para registrar las deducciones como cuentas por pagar.');
            }

            $deuda = new Prestamo(
                'RECIBIDO',
                $detalle['monto'],
                $detalle['nombre'],
                $cuentaDeduccion,
                $categoriaDeduccion,
                'Deducción generada automáticamente en el cierre de caja.'
            );
            $deuda->setDeduccionCaja($deduccion);
            $this->em->persist($deuda);
            $deudasGeneradas++;
        }

        $saldosActuales = [];
        foreach ($this->cuentaRepo->obtenerSaldosActuales() as $cuenta) {
            $cuentaId = (int)$cuenta['id'];
            $saldosActuales[$cuentaId] = (float)$cuenta['saldo'];
        }
        $esperadosPorCuenta = [];
        foreach ($saldosActuales as $cuentaId => $saldoActual) {
            $esperadosPorCuenta[(string)$cuentaId] = round($saldoActual + ($variacionPorCuenta[$cuentaId] ?? 0), 2);
        }
        $descuadresPorCuenta = [];
        foreach (($saldosContados['cuentas'] ?? []) as $cuentaId => $contado) {
            $descuadresPorCuenta[(string)$cuentaId] = round((float)$contado - ($esperadosPorCuenta[(string)$cuentaId] ?? 0), 2);
        }

        $contadoEfectivo = (float)($saldosContados['efectivo'] ?? 0);
        $contadoNequi = (float)($saldosContados['nequi'] ?? 0);
        if (isset($saldosContados['cuentas'])) {
            $contadoEfectivo = 0.0;
            $contadoNequi = 0.0;
            foreach ($this->cuentaRepo->obtenerSaldosActuales() as $cuenta) {
                if (!$cuenta['incluir_en_arqueo']) {
                    continue;
                }
                $contado = (float)($saldosContados['cuentas'][(string)$cuenta['id']] ?? 0);
                if ($cuenta['tipo'] === 'CAJA') {
                    $contadoEfectivo += $contado;
                } else {
                    $contadoNequi += $contado;
                }
            }
        }
        $descuadreEfectivo = round($contadoEfectivo - $esperadoEfectivo, 2);
        $descuadreNequi = round($contadoNequi - $esperadoNequi, 2);

        $this->em->flush();

        // Cada cuenta habilitada para arqueo se congela en una cuenta temporal.
        // La cuenta original queda en cero mediante movimientos auditables.
        $categoriaTransferencia = $this->categoriaRepo->buscarPorNombre('Transferencia entre cuentas');
        if (!$categoriaTransferencia) {
            $categoriaTransferencia = new \App\Domain\Entity\CategoriaFinanciera(
                'Transferencia entre cuentas',
                'MIXTO',
                'Movimientos internos entre cuentas financieras'
            );
            $this->em->persist($categoriaTransferencia);
        }

        $saldosFinales = $this->cuentaRepo->obtenerSaldosActuales();
        $planesTemporales = [];
        $marcaCierre = date('Y-m-d-His');
        $identificadorCierre = 'CIERRE-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
        foreach ($saldosFinales as $saldo) {
            if (!$saldo['incluir_en_arqueo'] || $saldo['es_temporal']) {
                continue;
            }

            $cuentaOrigen = $this->cuentaRepo->buscarPorId((int)$saldo['id']);
            if (!$cuentaOrigen) {
                continue;
            }

            $baseNombreTemporal = 'TEMP-' . $marcaCierre . '-' . $cuentaOrigen->getNombre();
            $nombreTemporal = mb_substr($baseNombreTemporal, 0, 100);
            $secuencia = 2;
            while ($this->cuentaRepo->buscarPorNombre($nombreTemporal)) {
                $sufijo = '-' . $secuencia++;
                $nombreTemporal = mb_substr($baseNombreTemporal, 0, 100 - mb_strlen($sufijo)) . $sufijo;
            }
            $cuentaTemporal = new CuentaFinanciera($nombreTemporal, $cuentaOrigen->getTipo(), 0);
            $cuentaTemporal->marcarComoTemporal();
            $cuentaTemporal->desactivarParaFacturacion();
            $cuentaTemporal->desactivarParaArqueo();
            $cuentaTemporal->desactivarParaBalance();
            $cuentaTemporal->desactivarParaEgresos();
            $cuentaTemporal->desactivarParaCuentasPorPagar();
            $this->em->persist($cuentaTemporal);

            $planesTemporales[] = [
                'origen' => $cuentaOrigen,
                'temporal' => $cuentaTemporal,
                'monto' => round(max(0, (float)$saldo['saldo']), 2),
            ];
        }
        $this->em->flush();

        $cuentasTemporales = [];
        foreach ($planesTemporales as $plan) {
            $origen = $plan['origen'];
            $temporal = $plan['temporal'];
            $monto = $plan['monto'];

            if ($monto > 0) {
                $this->em->persist(new MovimientoFinanciero(
                    'EGRESO',
                    $monto,
                    $categoriaTransferencia,
                    $origen,
                    'arqueo_temporal',
                    $temporal->getId(),
                    'Traslado temporal por arqueo a ' . $temporal->getNombre()
                ));
                $this->em->persist(new MovimientoFinanciero(
                    'INGRESO',
                    $monto,
                    $categoriaTransferencia,
                    $temporal,
                    'arqueo_temporal',
                    $origen->getId(),
                    'Saldo auditado desde ' . $origen->getNombre()
                ));
            }

            $this->em->persist(new TransferenciaArqueo(
                $temporal->getId(),
                $origen->getId(),
                $origen->getNombre(),
                $temporal->getNombre(),
                $monto,
                $auditadoPor,
                $identificadorCierre
            ));
            $cuentasTemporales[] = [
                'id' => $temporal->getId(),
                'nombre' => $temporal->getNombre(),
                'origen' => $origen->getNombre(),
                'monto' => $monto,
            ];
        }
        $this->em->flush();

        return [
            'facturas' => count($facturas),
            'gastos' => count($gastos),
            'abonos' => count($abonos),
            'deducciones' => $detalleDeducciones,
            'total_deducciones' => array_sum(array_column($detalleDeducciones, 'monto')),
            'esperado_efectivo' => round($esperadoEfectivo, 2),
            'esperado_nequi' => round($esperadoNequi, 2),
            'contado_efectivo' => round($contadoEfectivo, 2),
            'contado_nequi' => round($contadoNequi, 2),
            'descuadre_efectivo' => $descuadreEfectivo,
            'descuadre_nequi' => $descuadreNequi,
            'esperados_cuentas' => $esperadosPorCuenta,
            'descuadres_cuentas' => $descuadresPorCuenta,
            'deudas_deducciones_generadas' => $deudasGeneradas,
            'movimientos_creados' => $movimientosGenerados,
            'identificador_cierre' => $identificadorCierre,
            'copia_cierre' => '/api/core/caja/cierres/' . rawurlencode($identificadorCierre),
            'cuentas_temporales' => $cuentasTemporales
        ];
    }
}
