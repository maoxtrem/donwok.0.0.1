<?php

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pedido_items')]
class PedidoItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Pedido::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private Pedido $pedido;

    #[ORM\ManyToOne(targetEntity: Producto::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Producto $producto;

    #[ORM\Column]
    private int $cantidad;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $precioUnitario;

    public function __construct(Pedido $pedido, Producto $producto, int $cantidad, float $precioUnitario)
    {
        $this->pedido = $pedido;
        $this->producto = $producto;
        $this->cantidad = $cantidad;
        $this->precioUnitario = $precioUnitario;
    }

    public function getSubtotal(): float { return $this->cantidad * $this->precioUnitario; }

    public function toArray(): array
    {
        return [
            'producto' => $this->producto->getNombre(),
            'cantidad' => $this->cantidad,
            'precioUnitario' => $this->precioUnitario,
            'subtotal' => $this->getSubtotal()
        ];
    }
}
