<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\FacturaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FacturaRepository::class)]
#[ORM\Table(name: 'facturas')]
#[ORM\HasLifecycleCallbacks]
class Factura
{
    use \App\Domain\Entity\Traits\FechasTrait;

    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_TERMINADO = 'TERMINADO';
    public const ESTADO_FACTURADO = 'FACTURADO';
    public const ESTADO_CERRADA = 'CERRADA';
    public const ESTADO_ANULADA = 'ANULADA';

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

    #[ORM\OneToMany(mappedBy: 'factura', targetEntity: FacturaDetalle::class, cascade: ['persist', 'remove'])]
    private Collection $detalles;

    public function __construct()
    {
        $this->detalles = new ArrayCollection();
        $this->estado = self::ESTADO_PENDIENTE;
    }

    public function getId(): ?int { return $this->id; }
    public function getEstado(): string { return $this->estado; }
    public function getTotal(): float { return $this->total; }
    public function getNumeroFactura(): ?string { return $this->numeroFactura; }

    public function agregarItem(string $nombre, float $precio, float $costo, int $cantidad): void
    {
        $detalle = new FacturaDetalle($this, $nombre, $precio, $costo, $cantidad);
        $this->detalles->add($detalle);
        $this->total += ($precio * $cantidad);
    }

    public function marcarComoTerminado(): void 
    { 
        $this->estado = self::ESTADO_TERMINADO; 
    }
    
    public function facturar(string $numero): void 
    { 
        $this->numeroFactura = $numero;
        $this->estado = self::ESTADO_FACTURADO; 
    }

    public function cerrar(): void
    {
        $this->estado = self::ESTADO_CERRADA;
    }

    public function anular(): void
    {
        $this->estado = self::ESTADO_ANULADA;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numeroFactura' => $this->numeroFactura,
            'estado' => $this->estado,
            'total' => (float)$this->total,
            'fecha' => $this->getFechaCreacion() ? $this->getFechaCreacion()->format('Y-m-d H:i:s') : null,
            'items' => array_map(fn($d) => $d->toArray(), $this->detalles->toArray())
        ];
    }
}