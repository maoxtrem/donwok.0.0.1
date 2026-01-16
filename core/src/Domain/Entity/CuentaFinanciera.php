<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\CuentaFinancieraRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CuentaFinancieraRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CuentaFinanciera
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nombre;

    #[ORM\Column(length: 20)]
    private string $tipo; // CAJA | BANCO | BILLETERA

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private float $saldoInicial;
}