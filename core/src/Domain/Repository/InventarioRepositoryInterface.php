<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Inventario;

interface InventarioRepositoryInterface
{
    public function guardar(Inventario $inventario): void;
    public function buscarPorId(int $id): ?Inventario;
    public function buscarPorNombre(string $nombre): ?Inventario;

    /** @return Inventario[] */
    public function buscarTodos(): array;

    public function totalDineroInventario(): float;
}
