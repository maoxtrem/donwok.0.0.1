<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\FacturaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FacturaRepository::class)]
#[ORM\Table(name: 'facturas')]
#[ORM\HasLifecycleCallbacks]
class Factura
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Cliente $cliente;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $total;

    #[ORM\Column(length: 20)]
    private string $estado;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fechaEmision;
}