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
        $prestamos = $this->prestamoRepo->findPendientesCierre();
        $abonos = $this->pagoRepo->findPendientesCierre();
        
        if (empty($facturas) && empty($gastos) && empty($prestamos) && empty($abonos)) {
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
        if (!$catVentas) throw new \Exception("Categoría 'Ventas Diarias' no encontrada.");

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

        // --- 2. PROCESAR INGRESOS POR ABONOS (COBRO DE CARTERA) ---
        $catCobro = $this->categoriaRepo->buscarPorNombre('Cobro de Préstamo');
        if (!$catCobro && count($abonos) > 0) throw new \Exception("Categoría 'Cobro de Préstamo' no encontrada.");

        foreach ($abonos as $a) {
            $this->em->persist(new MovimientoFinanciero('INGRESO', $a->getMonto(), $catCobro, $a->getCuentaFinanciera(), 'prestamo', $a->getPrestamo()->getId(), "Cierre: Abono de {$a->getPrestamo()->getEntidad()}"));
            $a->cerrar();
            $movimientosGenerados++;
        }

        // --- 3. PROCESAR EGRESOS (GASTOS E INVERSIONES) ---
        foreach ($gastos as $g) {
            $this->em->persist(new MovimientoFinanciero('EGRESO', $g->getMonto(), $g->getCategoriaFinanciera(), $g->getCuentaFinanciera(), 'gasto', $g->getId(), "Cierre: {$g->getConcepto()}"));
            $g->cerrar();
            $movimientosGenerados++;
        }

        // --- 4. PROCESAR EGRESOS (PRÉSTAMOS OTORGADOS) ---
        $catPrestamoOtor = $this->categoriaRepo->buscarPorNombre('Préstamos Otorgados');
        if (!$catPrestamoOtor && count($prestamos) > 0) throw new \Exception("Categoría 'Préstamos Otorgados' no encontrada.");

        foreach ($prestamos as $p) {
            $this->em->persist(new MovimientoFinanciero('EGRESO', $p->getMontoTotal(), $catPrestamoOtor, $p->getCuentaFinanciera(), 'prestamo', $p->getId(), "Cierre: Préstamo a {$p->getEntidad()}"));
            $p->cerrar();
            $movimientosGenerados++;
        }

        // Cerrar facturas
        foreach ($facturas as $f) { $f->cerrar(); }

        $this->em->flush();

        return [
            'facturas' => count($facturas),
            'gastos' => count($gastos),
            'prestamos' => count($prestamos),
            'abonos' => count($abonos),
            'movimientos_creados' => $movimientosGenerados
        ];
    }
}