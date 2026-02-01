<?php

namespace App\Domain\Repository;

use App\Domain\Entity\FacturaDetalle;

interface FacturaDetalleRepositoryInterface
{
    /**
     * @return array Datos de rentabilidad: nombre, cantidad, total_venta, total_costo, ganancia
     */
    public function obtenerRentabilidadPorProducto(\DateTimeInterface $desde, \DateTimeInterface $hasta): array;
}
