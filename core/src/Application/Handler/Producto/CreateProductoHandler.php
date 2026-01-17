<?php
// src/Application/Handler/Producto/CreateProductoHandler.php
namespace App\Application\Handler\Producto;

use App\Application\DTO\Request\ProductoRequestDTO;
use App\Application\DTO\Response\ProductoResponseDTO;
use App\Domain\Entity\Producto;
use App\Domain\Repository\ProductoRepositoryInterface;

class CreateProductoHandler
{
    public function __construct(private ProductoRepositoryInterface $repo) {}

    public function handle(ProductoRequestDTO $dto): ProductoResponseDTO
    {
        // Crear la entidad usando el constructor completo
        $producto = new Producto(
            $dto->nombre,
            $dto->precioActual,
            $dto->costoActual,
            $dto->activo ?? true // por si no se envía activo
        );

        // Guardar en el repositorio
        $this->repo->guardar($producto);

        // Devolver ResponseDTO
        return new ProductoResponseDTO(
            $producto->getId(),
            $producto->getNombre(),
            $producto->getPrecioActual(),
            $producto->getCostoActual(),
            $producto->isActivo()
        );
    }
}
