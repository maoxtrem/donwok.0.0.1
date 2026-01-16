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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observaciones = null;
}