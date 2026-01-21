<?php

namespace App\Application\Handler\Finanzas;

use App\Domain\Entity\Factura;
use App\Domain\Entity\MovimientoFinanciero;
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
        private EntityManagerInterface $em
    ) {}

    public function handle(): array
    {
        $facturas = $this->facturaRepo->findPendientesCierre();
        $gastos = $this->gastoRepo->findPendientesCierre();
        $abonos = $this->pagoRepo->findPendientesCierre();
        
        if (empty($facturas) && empty($gastos) && empty($abonos)) {
            throw new \Exception("No hay movimientos pendientes de cierre.");
        }

        $movimientosGenerados = 0;

        // --- 1. PROCESAR INGRESOS POR VENTAS (FACTURAS) ---
        $totalVentaEfectivo = 0;
        $totalVentaNequi = 0;
        foreach ($facturas as $f) {
            $totalVentaEfectivo += $f->getPagoEfectivo();
            $totalVentaNequi += $f->getPagoNequi();
        }

        $catVentas = $this->categoriaRepo->buscarPorNombre('Ventas Diarias');
        if ($totalVentaEfectivo > 0) {
            $cuenta = $this->cuentaRepo->buscarPorNombre('Caja Principal');
            if ($cuenta) {
                $this->em->persist(new MovimientoFinanciero('INGRESO', $totalVentaEfectivo, $catVentas, $cuenta, 'venta', null, 'Cierre: Ventas Efectivo'));
                $movimientosGenerados++;
            }
        }
        if ($totalVentaNequi > 0) {
            $cuenta = $this->cuentaRepo->buscarPorNombre('Cuenta Nequi');
            if ($cuenta) {
                $this->em->persist(new MovimientoFinanciero('INGRESO', $totalVentaNequi, $catVentas, $cuenta, 'venta', null, 'Cierre: Ventas Nequi'));
                $movimientosGenerados++;
            }
        }

        // --- 2. PROCESAR ABONOS Y DESEMBOLSOS RECIBIDOS ---
        $catCobro = $this->categoriaRepo->buscarPorNombre('Cobro de Préstamo');
        $catIngresoCredito = $this->categoriaRepo->buscarPorNombre('Crédito');
        $catGastoDeuda = $this->categoriaRepo->buscarPorNombre('Pago de Obligaciones');

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
        }

        // --- 3. PROCESAR EGRESOS (GASTOS, INVERSIONES Y DESEMBOLSOS OTORGADOS) ---
        foreach ($gastos as $g) {
            $this->em->persist(new MovimientoFinanciero('EGRESO', $g->getMonto(), $g->getCategoriaFinanciera(), $g->getCuentaFinanciera(), 'gasto', $g->getId(), "Cierre: {$g->getConcepto()}"));
            $g->cerrar();
            $movimientosGenerados++;
        }

        // Cerrar facturas
        foreach ($facturas as $f) { $f->cerrar(); }

        $this->em->flush();

        return [
            'facturas' => count($facturas),
            'gastos' => count($gastos),
            'abonos' => count($abonos),
            'movimientos_creados' => $movimientosGenerados
        ];
    }
}
