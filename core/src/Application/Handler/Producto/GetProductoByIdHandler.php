<?php

namespace App\Application\Handler\Producto;

use App\Domain\Repository\ProductoRepositoryInterface;
use App\Application\DTO\Response\ProductoResponseDTO;

class GetProductoByIdHandler
{
    public function __construct(
        private ProductoRepositoryInterface $repo
    ) {}

    public function handle(int $id): ProductoResponseDTO
    {
        $producto = $this->repo->buscarPorId($id);

        if (!$producto || $producto->estaEliminado()) {
            throw new \RuntimeException('Producto no encontrado');
        }

        return new ProductoResponseDTO(
            $producto->getId(),
            $producto->getNombre(),
            $producto->getPrecioActual(),
            $producto->getCostoActual(),
            $producto->isActivo()
        );
    }
}
