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
}