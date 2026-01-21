<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\PrestamoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrestamoRepository::class)]
#[ORM\Table(name: 'prestamos')]
#[ORM\HasLifecycleCallbacks]
class Prestamo
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $tipo; // RECIBIDO | OTORGADO

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private float $montoTotal;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?float $tasaInteres = null;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $fechaInicio;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $fechaFin = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $entidad = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private CuentaFinanciera $cuentaFinanciera;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private float $saldoPendiente;

    #[ORM\Column(length: 20)]
    private string $estado; // PENDIENTE | PAGADO | ANULADO

    #[ORM\Column]
    private bool $isCerrado = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observaciones = null;

    public function __construct(
        string $tipo,
        float $montoTotal,
        string $entidad,
        CuentaFinanciera $cuenta,
        ?string $observaciones = null
    ) {
        $this->tipo = $tipo;
        $this->montoTotal = $montoTotal;
        $this->saldoPendiente = $montoTotal;
        $this->entidad = $entidad;
        $this->cuentaFinanciera = $cuenta;
        $this->observaciones = $observaciones;
        $this->estado = 'PENDIENTE';
        $this->isCerrado = false;
        $this->fechaInicio = new \DateTime();
    }

    public function getCuentaFinanciera(): CuentaFinanciera { return $this->cuentaFinanciera; }

    public function cerrar(): void { $this->isCerrado = true; }
    public function isCerrado(): bool { return $this->isCerrado; }

    public function registrarAbono(float $monto): void
    {
        $this->saldoPendiente -= $monto;
        if ($this->saldoPendiente <= 0) {
            $this->saldoPendiente = 0;
            $this->estado = 'PAGADO';
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getTipo(): string { return $this->tipo; }
    public function getMontoTotal(): float { return $this->montoTotal; }
    public function getSaldoPendiente(): float { return $this->saldoPendiente; }
    public function getEstado(): string { return $this->estado; }
    public function getEntidad(): ?string { return $this->entidad; }
}