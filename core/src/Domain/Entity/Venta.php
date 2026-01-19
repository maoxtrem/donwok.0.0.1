<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\VentaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VentaRepository::class)]
#[ORM\Table(name: 'ventas')]
#[ORM\HasLifecycleCallbacks]
class Venta
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Factura $factura;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $totalVenta;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $totalCosto;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $ganancia;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fechaVenta;
}