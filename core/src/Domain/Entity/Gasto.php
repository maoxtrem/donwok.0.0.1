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

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $proveedor = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observaciones = null;
}