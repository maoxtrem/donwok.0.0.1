<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\PedidoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PedidoRepository::class)]
#[ORM\Table(name: 'pedidos')]
#[ORM\HasLifecycleCallbacks]
class Pedido
{
    use \App\Domain\Entity\Traits\FechasTrait;

    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_PROCESADO = 'PROCESADO';
    public const ESTADO_CANCELADO = 'CANCELADO';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Factura::class)]
    #[ORM\JoinColumn(nullable: true)] // 🟢 Ahora es opcional para permitir la cola
    private ?Factura $factura = null;

    #[ORM\Column(length: 20)]
    private string $estado;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $total = 0;

    #[ORM\OneToMany(mappedBy: 'pedido', targetEntity: PedidoItem::class, cascade: ['persist', 'remove'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->estado = self::ESTADO_PENDIENTE;
        $this->fechaPedido = new \DateTime();
    }

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fechaPedido;

    // Getters y Métodos de negocio
    public function getId(): ?int { return $this->id; }
    public function getEstado(): string { return $this->estado; }
    public function getTotal(): float { return $this->total; }
    
    public function addItem(Producto $producto, int $cantidad): void
    {
        $item = new PedidoItem($this, $producto, $cantidad, $producto->getPrecioActual());
        $this->items->add($item);
        $this->total += $item->getSubtotal();
    }

    public function vincularFactura(Factura $factura): void
    {
        $this->factura = $factura;
        $this->estado = self::ESTADO_PROCESADO;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'estado' => $this->estado,
            'total' => $this->total,
            'fecha' => $this->fechaPedido->format('Y-m-d H:i:s'),
            'items' => array_map(fn($item) => $item->toArray(), $this->items->toArray())
        ];
    }
}
