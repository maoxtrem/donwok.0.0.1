<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Gasto;

interface GastoRepositoryInterface
{
    public function guardar(Gasto $gasto): void;
    
    /** @return Gasto[] */
    public function findPendientesCierre(): array;

    public function buscarPorId(int $id): ?Gasto;

    public function eliminar(Gasto $gasto): void;
}
