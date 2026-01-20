<?php

namespace App\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ventas')]
#[ORM\HasLifecycleCallbacks]
class Venta
{
    use \App\Domain\Entity\Traits\FechasTrait;

    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_TERMINADO = 'TERMINADO';
    public const ESTADO_FACTURADO = 'FACTURADO';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $estado;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $total = 0;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $numeroFactura = null;

    #[ORM\OneToMany(mappedBy: 'venta', targetEntity: VentaDetalle::class, cascade: ['persist', 'remove'])]
    private Collection $detalles;

    public function __construct()
    {
        $this->detalles = new ArrayCollection();
        $this->estado = self::ESTADO_PENDIENTE;
    }

    public function getId(): ?int { return $this->id; }
    public function getEstado(): string { return $this->estado; }
    public function getTotal(): float { return $this->total; }
    public function getDetalles(): Collection { return $this->detalles; }

    public function agregarItem(string $nombre, float $precio, float $costo, int $cantidad): void
    {
        $detalle = new VentaDetalle($this, $nombre, $precio, $costo, $cantidad);
        $this->detalles->add($detalle);
        $this->total += ($precio * $cantidad);
    }

    public function marcarComoTerminado(): void { $this->estado = self::ESTADO_TERMINADO; }
    
    public function facturar(string $numero): void 
    { 
        $this->numeroFactura = $numero;
        $this->estado = self::ESTADO_FACTURADO; 
    }

    public function getNumeroFactura(): ?string { return $this->numeroFactura; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numeroFactura' => $this->numeroFactura,
            'estado' => $this->estado,
            'total' => $this->total,
            'fecha' => $this->getFechaCreacion()->format('Y-m-d H:i:s'),
            'items' => array_map(fn($d) => $d->toArray(), $this->detalles->toArray())
        ];
    }
}
