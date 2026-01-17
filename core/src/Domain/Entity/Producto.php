<?php

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Infrastructure\Doctrine\Repository\ProductoRepository;

#[ORM\Table(name: 'productos')]
#[ORM\Entity(repositoryClass: ProductoRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Producto
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $nombre;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $precioActual;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $costoActual;

    #[ORM\Column]
    private bool $activo = true;

    // Constructor
    public function __construct(
        string $nombre,
        float $precioActual,
        float $costoActual,
        bool $activo = true
    ) {
        $this->nombre = $nombre;
        $this->precioActual = $precioActual;
        $this->costoActual = $costoActual;
        $this->activo = $activo;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getPrecioActual(): float
    {
        return $this->precioActual;
    }

    public function getCostoActual(): float
    {
        return $this->costoActual;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    // Métodos de negocio
    public function actualizarPrecio(float $nuevoPrecio): void
    {
        if ($nuevoPrecio <= 0) {
            throw new \InvalidArgumentException("El precio debe ser mayor que 0.");
        }
        $this->precioActual = $nuevoPrecio;
    }

    public function actualizarCosto(float $nuevoCosto): void
    {
        if ($nuevoCosto < 0) {
            throw new \InvalidArgumentException("El costo no puede ser negativo.");
        }
        $this->costoActual = $nuevoCosto;
    }

    public function activar(): void
    {
        $this->activo = true;
    }

    public function desactivar(): void
    {
        $this->activo = false;
    }
}
