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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observaciones = null;
}