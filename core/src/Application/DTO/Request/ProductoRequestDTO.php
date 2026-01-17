<?php
// src/Application/DTO/Request/ProductoRequestDTO.php
namespace App\Application\DTO\Request;

class ProductoRequestDTO
{
    public function __construct(
        public string $nombre,
        public float $precioActual,
        public float $costoActual,
        public ?bool $activo = true
    ) {}
}
