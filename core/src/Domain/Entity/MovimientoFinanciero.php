<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\MovimientoFinancieroRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MovimientoFinancieroRepository::class)]
#[ORM\Table(name: 'movimientos_financieros')]
#[ORM\HasLifecycleCallbacks]
class MovimientoFinanciero
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $tipoMovimiento; // INGRESO | EGRESO

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private float $monto;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fechaMovimiento;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private CategoriaFinanciera $categoriaFinanciera;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private CuentaFinanciera $cuentaFinanciera;

    #[ORM\Column(length: 50)]
    private string $referenciaTipo; // venta, gasto, prestamo, ajuste

    #[ORM\Column(nullable: true)]
    private ?int $referenciaId = null;

    public function __construct(
        string $tipoMovimiento,
        float $monto,
        CategoriaFinanciera $categoria,
        CuentaFinanciera $cuenta,
        string $referenciaTipo,
        ?int $referenciaId = null,
        ?string $descripcion = null
    ) {
        $this->tipoMovimiento = $tipoMovimiento;
        $this->monto = $monto;
        $this->categoriaFinanciera = $categoria;
        $this->cuentaFinanciera = $cuenta;
        $this->referenciaTipo = $referenciaTipo;
        $this->referenciaId = $referenciaId;
        $this->descripcion = $descripcion;
        $this->fechaMovimiento = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getTipoMovimiento(): string { return $this->tipoMovimiento; }
    public function getMonto(): float { return $this->monto; }
    public function getFechaMovimiento(): \DateTimeInterface { return $this->fechaMovimiento; }
    public function getDescripcion(): ?string { return $this->descripcion; }
    public function getCategoriaFinanciera(): CategoriaFinanciera { return $this->categoriaFinanciera; }
    public function getCuentaFinanciera(): CuentaFinanciera { return $this->cuentaFinanciera; }
    public function getReferenciaTipo(): string { return $this->referenciaTipo; }
    public function getReferenciaId(): ?int { return $this->referenciaId; }
}