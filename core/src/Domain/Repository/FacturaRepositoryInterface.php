<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Factura;

interface FacturaRepositoryInterface
{
    public function guardar(Factura $factura): void;
    
    public function buscarPorId(int $id): ?Factura;

    /**
     * @return Factura[]
     */
    public function findPendientesCierre(): array;
    
    public function getNextInvoiceNumber(): string;
}
