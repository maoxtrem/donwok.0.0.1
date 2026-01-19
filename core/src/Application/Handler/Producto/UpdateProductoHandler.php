<?php
// src/Application/Handler/Producto/UpdateProductoHandler.php
namespace App\Application\Handler\Producto;

use App\Application\DTO\Request\ProductoRequestDTO;
use App\Application\DTO\Response\ProductoResponseDTO;
use App\Domain\Repository\ProductoRepositoryInterface;

class UpdateProductoHandler
{
    public function __construct(private ProductoRepositoryInterface $repo) {}

    public function handle(int $id, ProductoRequestDTO $dto): ProductoResponseDTO
    {
        $producto = $this->repo->buscarPorId($id);

        if (!$producto) {
            throw new \RuntimeException("Producto no encontrado.");
        }

        // Usamos métodos de negocio de la entidad
        $producto->actualizarPrecio($dto->precioActual);
        $producto->actualizarCosto($dto->costoActual);
        if ($dto->activo) {
            $producto->activar();
        } else {
            $producto->desactivar();
        }

        $this->repo->guardar($producto);

        return new ProductoResponseDTO(
            $producto->getId(),
            $producto->getNombre(),
            $producto->getPrecioActual(),
            $producto->getCostoActual(),
            $producto->isActivo()
        );
    }
}
