<?php

namespace App\Application\Assembler;

use App\Domain\Entity\Producto;
use App\Application\DTO\Response\ProductoResponseDTO;

class ProductoResponseAssembler
{
    public static function fromEntity(Producto $producto): ProductoResponseDTO
    {
        return new ProductoResponseDTO(
            $producto->getId(),
            $producto->getNombre(),
            $producto->getPrecioActual(),
            $producto->getCostoActual(),
            $producto->isActivo()
        );
    }
}
