<?php

namespace App\Domain\Repository;

use App\Domain\Entity\PagoPrestamo;

interface PagoPrestamoRepositoryInterface
{
    /** @return PagoPrestamo[] */
    public function findPendientesCierre(): array;
}
