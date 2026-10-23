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
    public const ESTADO_PREPARACION = self::ESTADO_PENDIENTE;
    public const ESTADO_EMPAQUE = 'EMPAQUE';
    public const ESTADO_TERMINADO = 'TERMINADO';
    public const ESTADO_ENTREGADO = 'ENTREGADO';
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

    #[ORM\Column(type: 'json')]
    private array $pagos = [];

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $numeroFactura = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false], nullable: true)]
    private ?bool $esPago = false;

    #[ORM\Column(length: 20, options: ['default' => 'MESA'], nullable: true)]
    private ?string $tipo = 'MESA';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $clienteNombre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $domicilioDescripcion = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $editada = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $fechaUltimaEdicion = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $editadaPor = null;

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
    public function getPagos(): array { return $this->pagos; }
    public function getNumeroFactura(): ?string { return $this->numeroFactura; }
    public function isEsPago(): bool { return (bool)($this->esPago ?? false); }
    public function setEsPago(?bool $esPago): void { $this->esPago = $esPago; }

    public function getTipo(): string { return $this->tipo ?? 'MESA'; }
    public function setTipo(?string $tipo): void { $this->tipo = $tipo; }
    public function getClienteNombre(): ?string { return $this->clienteNombre; }
    public function getDomicilioDescripcion(): ?string { return $this->domicilioDescripcion; }
    public function setDatosCliente(?string $nombre, ?string $domicilio): void
    {
        $this->clienteNombre = $nombre ? trim(mb_substr($nombre, 0, 100)) : null;
        $this->domicilioDescripcion = $domicilio ? trim(mb_substr($domicilio, 0, 2000)) : null;
    }
    public function getDetalles(): Collection { return $this->detalles; }
    public function fueEditada(): bool { return $this->editada; }

    public function agregarItem(string $nombre, float $precio, float $costo, int $cantidad, ?int $productoId = null): void
    {
        $detalle = new FacturaDetalle($this, $nombre, $precio, $costo, $cantidad, $productoId);
        $this->detalles->add($detalle);
        $this->total += ($precio * $cantidad);
    }

    public function reemplazarItems(array $items, string $usuario = 'sistema'): void
    {
        if ($this->numeroFactura !== null) {
            throw new \Exception('No se puede modificar un pedido que ya fue facturado.');
        }

        foreach ($this->detalles as $detalle) {
            if ($detalle->estaActivo()) {
                $detalle->marcarComoEliminado($usuario);
            }
        }
        $this->total = 0;
        $this->editada = true;
        $this->fechaUltimaEdicion = new \DateTime();
        $this->editadaPor = $usuario;
        foreach ($items as $item) {
            $this->agregarItem(
                $item['nombre'],
                (float)$item['precio'],
                (float)$item['costo'],
                (int)$item['cantidad'],
                isset($item['productoId']) ? (int)$item['productoId'] : null
            );
        }
    }

    public function marcarComoEmpaque(): void
    {
        if ($this->estado !== self::ESTADO_PENDIENTE) {
            throw new \Exception('Solo se puede pasar a empaque un pedido en preparación.');
        }

        $this->estado = self::ESTADO_EMPAQUE;
    }

    public function marcarComoTerminado(): void
    {
        if (!in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_EMPAQUE], true)) {
            throw new \Exception('Solo se puede terminar un pedido en preparación o empaque.');
        }

        $this->estado = self::ESTADO_TERMINADO;
    }

    public function marcarComoEntregado(): void
    {
        if ($this->estado !== self::ESTADO_TERMINADO) {
            throw new \Exception('Solo se puede entregar un pedido terminado.');
        }

        if ($this->numeroFactura === null) {
            throw new \Exception('No se puede entregar un pedido sin factura. Primero debe facturarlo e imprimir la factura.');
        }

        $this->estado = self::ESTADO_ENTREGADO;
    }
    
    public function facturar(string $numero, float $efectivo = 0, float $nequi = 0): void 
    { 
        if ($this->numeroFactura !== null) {
            throw new \Exception('Este pedido ya fue facturado anteriormente');
        }

        if (abs(($efectivo + $nequi) - $this->total) > 0.01) {
            throw new \Exception("La suma de los pagos ($efectivo + $nequi) debe ser igual al total de la factura ({$this->total})");
        }

        $this->numeroFactura = $numero;
        $this->pagoEfectivo = $efectivo;
        $this->pagoNequi = $nequi;
        // Facturar no termina el pedido: producción debe mantenerlo en cola.
        $this->esPago = true;
    }

    public function facturarConPagos(string $numero, array $pagos): void
    {
        $totalPagado = array_reduce($pagos, fn(float $total, array $p) => $total + (float)$p['monto'], 0.0);
        if (abs($totalPagado - $this->total) > 0.01) {
            throw new \Exception("La distribución de pagos ($totalPagado) debe coincidir con el total de la factura ({$this->total})");
        }

        $this->numeroFactura = $numero;
        $this->pagos = array_values($pagos);
        $this->pagoEfectivo = array_reduce($pagos, fn(float $total, array $p) => $total + (($p['tipo'] ?? null) === 'CAJA' ? (float)$p['monto'] : 0), 0.0);
        $this->pagoNequi = array_reduce($pagos, fn(float $total, array $p) => $total + (($p['tipo'] ?? null) !== 'CAJA' ? (float)$p['monto'] : 0), 0.0);
        $this->esPago = true;
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
        $fecha = $this->getFechaCreacion()?->setTimezone(new \DateTimeZone('America/Bogota'));

        return [
            'id' => $this->id,
            'numeroFactura' => $this->numeroFactura,
            'estado' => $this->estado,
            'total' => (float)$this->total,
            'pagoEfectivo' => (float)$this->pagoEfectivo,
            'pagoNequi' => (float)$this->pagoNequi,
            'esPago' => $this->isEsPago(),
            'tipo' => $this->getTipo(),
            'clienteNombre' => $this->getClienteNombre(),
            'domicilioDescripcion' => $this->getDomicilioDescripcion(),
            'fecha' => $fecha?->format('Y-m-d H:i:s'),
            'editada' => $this->editada,
            'fechaEdicion' => $this->fechaUltimaEdicion?->format('Y-m-d H:i:s'),
            'editadaPor' => $this->editadaPor,
            'items' => array_values(array_map(
                fn($d) => $d->toArray(),
                array_filter($this->detalles->toArray(), fn($d) => $d->estaActivo())
            )),
            'historialDetalles' => array_values(array_map(
                fn($d) => [
                    ...$d->toArray(),
                    'estado' => $d->getEstado(),
                    'fechaEliminacion' => $d->getFechaEliminacion()?->format('Y-m-d H:i:s'),
                    'eliminadoPor' => $d->getEliminadoPor(),
                ],
                array_filter($this->detalles->toArray(), fn($d) => !$d->estaActivo())
            ))
        ];
    }
}
