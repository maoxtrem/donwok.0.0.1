<?php
// src/Application/DTO/Response/ProductoResponseDTO.php
namespace App\Application\DTO\Response;

class ProductoResponseDTO
{
    public function __construct(
        public int $id,
        public string $nombre,
        public float $precioActual,
        public float $costoActual,
        public bool $activo
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'precioActual' => $this->precioActual,
            'costoActual' => $this->costoActual,
            'activo' => $this->activo
        ];
    }
}
