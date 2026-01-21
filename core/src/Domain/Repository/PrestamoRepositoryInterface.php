<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Prestamo;

interface PrestamoRepositoryInterface
{
    public function guardar(Prestamo $prestamo): void;
    public function buscarPorId(int $id): ?Prestamo;
    
    /** @return Prestamo[] */
    public function buscarActivos(): array;

    /** @return Prestamo[] */
    public function buscarDeudasPendientes(): array;

    public function eliminar(Prestamo $prestamo): void;
}
