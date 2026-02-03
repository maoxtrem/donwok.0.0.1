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

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $pagoEfectivo = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $pagoNequi = 0;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $numeroFactura = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $numeroTicket = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false], nullable: true)]
    private ?bool $esPago = false;

    #[ORM\Column(length: 20, options: ['default' => 'MESA'], nullable: true)]
    private ?string $tipo = 'MESA';

    #[ORM\OneToMany(mappedBy: 'factura', targetEntity: FacturaDetalle::class, cascade: ['persist', 'remove'])]
    private Collection $detalles;

    public function __construct()
    {
        $this->detalles = new ArrayCollection();
        $this->estado = self::ESTADO_PENDIENTE;
        $this->pagoEfectivo = 0;
        $this->pagoNequi = 0;
        $this->esPago = false;
        $this->tipo = 'MESA';
    }

    public function getId(): ?int { return $this->id; }
    public function getEstado(): string { return $this->estado; }
    public function getTotal(): float { return $this->total; }
    public function getPagoEfectivo(): float { return $this->pagoEfectivo; }
    public function getPagoNequi(): float { return $this->pagoNequi; }
    public function getNumeroFactura(): ?string { return $this->numeroFactura; }
    public function getNumeroTicket(): ?int { return $this->numeroTicket; }
    public function setNumeroTicket(int $numero): void { $this->numeroTicket = $numero; }

    public function isEsPago(): bool { return (bool)($this->esPago ?? false); }
    public function setEsPago(?bool $esPago): void { $this->esPago = $esPago; }

    public function getTipo(): string { return $this->tipo ?? 'MESA'; }
    public function setTipo(?string $tipo): void { $this->tipo = $tipo; }

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
    
    public function facturar(string $numero, float $efectivo = 0, float $nequi = 0): void 
    { 
        if (abs(($efectivo + $nequi) - $this->total) > 0.01) {
            throw new \Exception("La suma de los pagos ($efectivo + $nequi) debe ser igual al total de la factura ({$this->total})");
        }

        $this->numeroFactura = $numero;
        $this->pagoEfectivo = $efectivo;
        $this->pagoNequi = $nequi;
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
            'numeroTicket' => $this->numeroTicket,
            'estado' => $this->estado,
            'total' => (float)$this->total,
            'pagoEfectivo' => (float)$this->pagoEfectivo,
            'pagoNequi' => (float)$this->pagoNequi,
            'esPago' => $this->isEsPago(),
            'tipo' => $this->getTipo(),
            'fecha' => $this->getFechaCreacion() ? $this->getFechaCreacion()->format('Y-m-d H:i:s') : null,
            'items' => array_map(fn($d) => $d->toArray(), $this->detalles->toArray())
        ];
    }
}