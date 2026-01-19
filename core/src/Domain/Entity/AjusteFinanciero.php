<?php

namespace App\Domain\Entity;

use  App\Infrastructure\Doctrine\Repository\AjusteFinancieroRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AjusteFinancieroRepository::class)]
#[ORM\Table(name: 'ajustes_financieros')]
#[ORM\HasLifecycleCallbacks]
class AjusteFinanciero
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $tipo; // INGRESO | EGRESO

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private float $monto;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $fecha;

    #[ORM\Column(type: 'text')]
    private string $motivo;
}