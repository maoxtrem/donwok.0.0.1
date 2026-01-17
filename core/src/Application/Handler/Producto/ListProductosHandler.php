<?php
// src/Application/Handler/Producto/ListProductosHandler.php
namespace App\Application\Handler\Producto;

use App\Application\DTO\Response\ProductoResponseDTO;
use App\Domain\Repository\ProductoRepositoryInterface;

class ListProductosHandler
{
    public function __construct(private ProductoRepositoryInterface $repo) {}

    public function handle(): array
    {
        $productos = $this->repo->buscarTodos(); // asumimos método del repo

        return array_map(
            fn($p) => new ProductoResponseDTO(
                $p->getId(),
                $p->getNombre(),
                $p->getPrecioActual(),
                $p->getCostoActual(),
                $p->isActivo()
            ),
            $productos
        );
    }
}
