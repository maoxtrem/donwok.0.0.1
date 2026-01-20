<?php

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'venta_detalles')]
class VentaDetalle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Venta::class, inversedBy: 'detalles')]
    #[ORM\JoinColumn(nullable: false)]
    private Venta $venta;

    #[ORM\Column(length: 150)]
    private string $nombreProducto;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $precioUnitario;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $costoUnitario;

    #[ORM\Column]
    private int $cantidad;

    public function __construct(Venta $venta, string $nombre, float $precio, float $costo, int $cantidad)
    {
        $this->venta = $venta;
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
            'precio' => $this->precioUnitario,
            'subtotal' => $this->precioUnitario * $this->cantidad
        ];
    }
}
