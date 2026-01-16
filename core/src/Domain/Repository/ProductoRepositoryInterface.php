<?php
namespace App\Domain\Repository;

use App\Domain\Entity\Producto;

interface ProductoRepositoryInterface
{
    public function guardar(Producto $producto): void;

    public function buscarPorId(int $id): ?Producto;

    public function buscarPorNombre(string $nombre): ?Producto;

    /** @return Producto[] */
    public function buscarTodos(): array;

    public function existeConNombre(string $nombre): bool;
}
