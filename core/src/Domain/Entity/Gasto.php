<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\GastoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GastoRepository::class)]
#[ORM\Table(name: 'gastos')]
#[ORM\HasLifecycleCallbacks]
class Gasto
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $concepto;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private float $monto;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fechaGasto;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private CategoriaFinanciera $categoriaFinanciera;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private CuentaFinanciera $cuentaFinanciera;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $proveedor = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observaciones = null;

    #[ORM\Column]
    private bool $isCerrado = false;

    #[ORM\Column]
    private bool $esACredito = false;

    public function __construct(
        string $concepto,
        float $monto,
        CategoriaFinanciera $categoria,
        CuentaFinanciera $cuenta,
        ?string $proveedor = null,
        ?string $observaciones = null,
        bool $esACredito = false
    ) {
        $this->concepto = $concepto;
        $this->monto = $monto;
        $this->categoriaFinanciera = $categoria;
        $this->cuentaFinanciera = $cuenta;
        $this->proveedor = $proveedor;
        $this->observaciones = $observaciones;
        $this->esACredito = $esACredito;
        $this->fechaGasto = new \DateTime();
        $this->isCerrado = false;
    }

    public function isACredito(): bool { return $this->esACredito; }

    public function getCuentaFinanciera(): CuentaFinanciera { return $this->cuentaFinanciera; }

    public function cerrar(): void { $this->isCerrado = true; }
    public function isCerrado(): bool { return $this->isCerrado; }

    public function getId(): ?int { return $this->id; }
    public function getConcepto(): string { return $this->concepto; }
    public function getMonto(): float { return $this->monto; }
    public function getFechaGasto(): \DateTimeInterface { return $this->fechaGasto; }
    public function getCategoriaFinanciera(): CategoriaFinanciera { return $this->categoriaFinanciera; }
    public function getProveedor(): ?string { return $this->proveedor; }
    public function getObservaciones(): ?string { return $this->observaciones; }
}