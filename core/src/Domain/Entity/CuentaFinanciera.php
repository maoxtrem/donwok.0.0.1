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

    #[ORM\Column(options: ['default' => true])]
    private bool $activa = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $usarEnFacturacion = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $incluirEnArqueo = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $mostrarEnBalance = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $usarEnEgresos = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $usarEnCuentasPorPagar = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $esTemporal = false;

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
    public function estaActiva(): bool { return $this->activa; }
    public function deshabilitar(): void { $this->activa = false; }
    public function habilitar(): void { $this->activa = true; }
    public function usarEnFacturacion(): bool { return $this->usarEnFacturacion; }
    public function activarParaFacturacion(): void { $this->usarEnFacturacion = true; }
    public function desactivarParaFacturacion(): void { $this->usarEnFacturacion = false; }
    public function incluirEnArqueo(): bool { return $this->incluirEnArqueo; }
    public function activarParaArqueo(): void { $this->incluirEnArqueo = true; }
    public function desactivarParaArqueo(): void { $this->incluirEnArqueo = false; }
    public function mostrarEnBalance(): bool { return $this->mostrarEnBalance; }
    public function activarParaBalance(): void { $this->mostrarEnBalance = true; }
    public function desactivarParaBalance(): void { $this->mostrarEnBalance = false; }
    public function usarEnEgresos(): bool { return $this->usarEnEgresos; }
    public function activarParaEgresos(): void { $this->usarEnEgresos = true; }
    public function desactivarParaEgresos(): void { $this->usarEnEgresos = false; }
    public function usarEnCuentasPorPagar(): bool { return $this->usarEnCuentasPorPagar; }
    public function activarParaCuentasPorPagar(): void { $this->usarEnCuentasPorPagar = true; }
    public function desactivarParaCuentasPorPagar(): void { $this->usarEnCuentasPorPagar = false; }
    public function esTemporal(): bool { return $this->esTemporal; }
    public function marcarComoTemporal(): void { $this->esTemporal = true; }
}
