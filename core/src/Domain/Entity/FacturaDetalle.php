<?php

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'factura_detalles')]
class FacturaDetalle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Factura::class, inversedBy: 'detalles')]
    #[ORM\JoinColumn(nullable: false)]
    private Factura $factura;

    #[ORM\Column(length: 150)]
    private string $nombreProducto;

    #[ORM\Column(nullable: true)]
    private ?int $productoId = null;

    #[ORM\Column(length: 20, options: ['default' => 'ACTIVO'])]
    private string $estado = 'ACTIVO';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $fechaEliminacion = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $eliminadoPor = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $precioUnitario;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $costoUnitario;

    #[ORM\Column]
    private int $cantidad;

    public function __construct(Factura $factura, string $nombre, float $precio, float $costo, int $cantidad, ?int $productoId = null)
    {
        $this->factura = $factura;
        $this->nombreProducto = $nombre;
        $this->precioUnitario = $precio;
        $this->costoUnitario = $costo;
        $this->cantidad = $cantidad;
        $this->productoId = $productoId;
    }

    public function toArray(): array
    {
        return [
            'producto' => $this->nombreProducto,
            'productoId' => $this->productoId,
            'cantidad' => $this->cantidad,
            'precio' => (float)$this->precioUnitario,
            'costo' => (float)$this->costoUnitario,
            'subtotal' => (float)($this->precioUnitario * $this->cantidad)
        ];
    }

    public function estaActivo(): bool { return $this->estado === 'ACTIVO'; }
    public function marcarComoEliminado(string $usuario): void
    {
        $this->estado = 'ELIMINADO';
        $this->fechaEliminacion = new \DateTime();
        $this->eliminadoPor = $usuario;
    }
    public function getEstado(): string { return $this->estado; }
    public function getFechaEliminacion(): ?\DateTimeInterface { return $this->fechaEliminacion; }
    public function getEliminadoPor(): ?string { return $this->eliminadoPor; }

    public function getNombreProducto(): string { return $this->nombreProducto; }
    public function getProductoId(): ?int { return $this->productoId; }
    public function getCantidad(): int { return $this->cantidad; }
    public function getPrecioUnitario(): float { return (float)$this->precioUnitario; }
}
