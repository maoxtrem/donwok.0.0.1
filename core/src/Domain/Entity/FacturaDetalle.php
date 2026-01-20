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

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $precioUnitario;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $costoUnitario;

    #[ORM\Column]
    private int $cantidad;

    public function __construct(Factura $factura, string $nombre, float $precio, float $costo, int $cantidad)
    {
        $this->factura = $factura;
        $this->nombreProducto = $nombre;
        $this->precioUnitario = $precio;
        $this->costoUnitario = $costo;
        $this->cantidad = $cantidad;
    }

    public function toArray(): array
    {
        return [
            'producto' => $this->nombreProducto,
            'cantidad' => $this->cantidad,
            'precio' => (float)$this->precioUnitario,
            'subtotal' => (float)($this->precioUnitario * $this->cantidad)
        ];
    }
}
