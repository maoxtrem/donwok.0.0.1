<?php
// src/Application/Handler/Producto/DeleteProductoHandler.php
namespace App\Application\Handler\Producto;

use App\Domain\Repository\ProductoRepositoryInterface;

class DeleteProductoHandler
{
    public function __construct(private ProductoRepositoryInterface $repo) {}

    public function handle(int $id): void
    {
        $producto = $this->repo->buscarPorId($id);

        if (!$producto) {
            throw new \RuntimeException("Producto no encontrado.");
        }
        $producto->eliminarLogicamente();
        $this->repo->guardar($producto); // asumimos método eliminar en repo
    }
}
