<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\TurnoPuestoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TurnoPuestoRepository::class)]
#[ORM\Table(name: 'turnos_puesto')]
#[ORM\HasLifecycleCallbacks]
class TurnoPuesto
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PuestoTrabajo::class)]
    #[ORM\JoinColumn(nullable: false)]
    private PuestoTrabajo $puesto;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $inicio;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $cierre = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private float $valorHora;

    #[ORM\Column(type: 'integer')]
    private int $denominacionMinima;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?float $montoPagar = null;

    #[ORM\Column(length: 64)]
    private string $codigoCierreHash;

    private string $codigoCierre;

    private ?int $minutosLaborados = null;

    public function __construct(PuestoTrabajo $puesto, float $valorHora, int $denominacionMinima)
    {
        $this->puesto = $puesto;
        $this->valorHora = $valorHora;
        $this->denominacionMinima = $denominacionMinima;
        $this->inicio = new \DateTime();
        $this->codigoCierre = $this->generarCodigo();
        $this->codigoCierreHash = hash('sha256', $this->normalizarCodigo($this->codigoCierre));
    }

    public function cerrar(): void
    {
        $this->cierre = new \DateTime();
        $this->minutosLaborados = intdiv(max(0, $this->cierre->getTimestamp() - $this->inicio->getTimestamp()), 60);
        $montoExacto = $this->minutosLaborados * ($this->valorHora / 60);
        $this->montoPagar = ceil($montoExacto / $this->denominacionMinima) * $this->denominacionMinima;
    }

    public function codigoValido(string $codigo): bool
    {
        return hash_equals($this->codigoCierreHash, hash('sha256', $this->normalizarCodigo($codigo)));
    }

    public function getCodigoCierre(): string { return $this->codigoCierre; }

    public function getId(): ?int { return $this->id; }
    public function getPuesto(): PuestoTrabajo { return $this->puesto; }
    public function getInicio(): \DateTimeInterface { return $this->inicio; }
    public function getCierre(): ?\DateTimeInterface { return $this->cierre; }
    public function getValorHora(): float { return $this->valorHora; }
    public function getDenominacionMinima(): int { return $this->denominacionMinima; }
    public function getMontoPagar(): ?float { return $this->montoPagar; }
    public function getMinutosLaborados(): ?int
    {
        if ($this->minutosLaborados !== null) {
            return $this->minutosLaborados;
        }
        if (!$this->cierre) {
            return null;
        }
        return intdiv(max(0, $this->cierre->getTimestamp() - $this->inicio->getTimestamp()), 60);
    }
    public function estaActivo(): bool { return $this->cierre === null; }

    private function generarCodigo(): string
    {
        $letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $codigo = '';
        for ($i = 0; $i < 4; $i++) {
            $codigo .= $letras[random_int(0, strlen($letras) - 1)];
        }
        return substr($codigo, 0, 2) . '-' . substr($codigo, 2, 2);
    }

    private function normalizarCodigo(string $codigo): string
    {
        return strtoupper((string)preg_replace('/[^A-Z]/i', '', $codigo));
    }
}
