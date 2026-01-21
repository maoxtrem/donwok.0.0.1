<?php

namespace App\Domain\Repository;

use App\Domain\Entity\MovimientoFinanciero;

interface MovimientoFinancieroRepositoryInterface
{
    public function guardar(MovimientoFinanciero $movimiento): void;
    
    /** @return MovimientoFinanciero[] */
    public function buscarRecientes(int $limite = 50): array;

    /**
     * @return array Resumen de totales agrupados por fecha y tipo
     */
    public function obtenerTotalesPorPeriodo(\DateTimeInterface $desde, \DateTimeInterface $hasta): array;
}
