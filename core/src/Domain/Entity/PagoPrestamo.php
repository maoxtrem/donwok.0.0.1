<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\PagoPrestamoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PagoPrestamoRepository::class)]
#[ORM\Table(name: 'pagos_prestamo')]
#[ORM\HasLifecycleCallbacks]
class PagoPrestamo
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Prestamo $prestamo;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private float $monto;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $fechaPago;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private CuentaFinanciera $cuentaFinanciera;

    #[ORM\Column]
    private bool $isCerrado = false;

    #[ORM\Column]
    private bool $esDesembolso = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observaciones = null;

    public function __construct(Prestamo $prestamo, float $monto, CuentaFinanciera $cuenta, ?string $obs = null, bool $esDesembolso = false)
    {
        $this->prestamo = $prestamo;
        $this->monto = $monto;
        $this->cuentaFinanciera = $cuenta;
        $this->observaciones = $obs;
        $this->esDesembolso = $esDesembolso;
        $this->fechaPago = new \DateTime();
        $this->isCerrado = false;
    }

    public function cerrar(): void { $this->isCerrado = true; }
    public function getId(): ?int { return $this->id; }
    public function getMonto(): float { return $this->monto; }
    public function getCuentaFinanciera(): CuentaFinanciera { return $this->cuentaFinanciera; }
    public function getPrestamo(): Prestamo { return $this->prestamo; }
    public function esDesembolso(): bool { return $this->esDesembolso; }
}