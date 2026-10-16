<?php

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'transferencias_arqueo')]
class TransferenciaArqueo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $cuentaTemporalId;

    #[ORM\Column]
    private int $cuentaOrigenId;

    #[ORM\Column(nullable: true)]
    private ?int $cuentaDestinoId = null;

    #[ORM\Column(length: 100)]
    private string $cuentaOrigenNombre;

    #[ORM\Column(length: 120)]
    private string $cuentaTemporalNombre;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $cuentaDestinoNombre = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private float $monto;

    #[ORM\Column(length: 150)]
    private string $auditadoPor;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $movidoPor = null;

    #[ORM\Column(length: 20)]
    private string $estado = 'PENDIENTE';

    #[ORM\Column(length: 50)]
    private string $identificadorCierre;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fechaArqueo;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $fechaTraslado = null;

    public function __construct(
        int $cuentaTemporalId,
        int $cuentaOrigenId,
        string $cuentaOrigenNombre,
        string $cuentaTemporalNombre,
        float $monto,
        string $auditadoPor,
        string $identificadorCierre = 'LEGACY'
    ) {
        $this->cuentaTemporalId = $cuentaTemporalId;
        $this->cuentaOrigenId = $cuentaOrigenId;
        $this->cuentaOrigenNombre = $cuentaOrigenNombre;
        $this->cuentaTemporalNombre = $cuentaTemporalNombre;
        $this->monto = $monto;
        $this->auditadoPor = $auditadoPor;
        $this->identificadorCierre = $identificadorCierre;
        $this->fechaArqueo = new \DateTime();
    }

    public function getIdentificadorCierre(): string { return $this->identificadorCierre; }
    public function getCuentaOrigenNombre(): string { return $this->cuentaOrigenNombre; }
    public function getCuentaTemporalNombre(): string { return $this->cuentaTemporalNombre; }
    public function getCuentaDestinoNombre(): ?string { return $this->cuentaDestinoNombre; }
    public function getMonto(): float { return $this->monto; }
    public function getAuditadoPor(): string { return $this->auditadoPor; }
    public function getMovidoPor(): ?string { return $this->movidoPor; }
    public function getEstado(): string { return $this->estado; }
    public function getFechaArqueo(): \DateTimeInterface { return $this->fechaArqueo; }
    public function getFechaTraslado(): ?\DateTimeInterface { return $this->fechaTraslado; }

    public function marcarComoTrasladada(int $cuentaDestinoId, string $cuentaDestinoNombre, string $movidoPor): void
    {
        $this->cuentaDestinoId = $cuentaDestinoId;
        $this->cuentaDestinoNombre = $cuentaDestinoNombre;
        $this->movidoPor = $movidoPor;
        $this->estado = 'TRASLADADO';
        $this->fechaTraslado = new \DateTime();
    }
}
