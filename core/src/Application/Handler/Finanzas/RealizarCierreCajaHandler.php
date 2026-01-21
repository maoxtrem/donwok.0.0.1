<?php

namespace App\Application\Handler\Finanzas;

use App\Domain\Entity\Factura;
use App\Domain\Entity\MovimientoFinanciero;
use App\Domain\Repository\FacturaRepositoryInterface;
use App\Infrastructure\Doctrine\Repository\CategoriaFinancieraRepository;
use App\Infrastructure\Doctrine\Repository\CuentaFinancieraRepository;
use Doctrine\ORM\EntityManagerInterface;

class RealizarCierreCajaHandler
{
    public function __construct(
        private FacturaRepositoryInterface $facturaRepo,
        private CategoriaFinancieraRepository $categoriaRepo,
        private CuentaFinancieraRepository $cuentaRepo,
        private EntityManagerInterface $em
    ) {}

    public function handle(): array
    {
        $facturas = $this->facturaRepo->findPendientesCierre();
        
        if (empty($facturas)) {
            throw new \Exception("No hay facturas pendientes de cierre.");
        }

        $totalVentas = 0;
        foreach ($facturas as $factura) {
            $totalVentas += $factura->getTotal();
        }

        // Buscar o crear una categoría para ventas diarias
        $categoria = $this->categoriaRepo->findOneBy(['nombre' => 'Ventas Diarias']);
        if (!$categoria) {
            // Esto es un fallback, idealmente debería estar configurado
            throw new \Exception("La categoría 'Ventas Diarias' no existe. Por favor créala.");
        }

        // Buscar o crear una cuenta de caja
        $cuenta = $this->cuentaRepo->findOneBy(['tipo' => 'CAJA']);
        if (!$cuenta) {
            throw new \Exception("No existe una cuenta de tipo 'CAJA'.");
        }

        $movimiento = new MovimientoFinanciero(
            'INGRESO',
            $totalVentas,
            $categoria,
            $cuenta,
            'venta',
            null,
            'Cierre de caja - Consolidad de ' . count($facturas) . ' facturas.'
        );

        $this->em->persist($movimiento);

        foreach ($facturas as $factura) {
            $factura->cerrar();
        }

        $this->em->flush();

        return [
            'movimiento_id' => $movimiento->getId(),
            'total' => $totalVentas,
            'cantidad_facturas' => count($facturas)
        ];
    }
}
