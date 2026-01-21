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

    public function __construct(string $nombre, string $tipo, float $saldoInicial = 0)
    {
        $this->nombre = $nombre;
        $this->tipo = $tipo;
        $this->saldoInicial = $saldoInicial;
    }

    public function getId(): ?int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
    public function getTipo(): string { return $this->tipo; }
    public function getSaldoInicial(): float { return $this->saldoInicial; }
}