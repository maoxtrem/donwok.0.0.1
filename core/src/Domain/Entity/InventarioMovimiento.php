<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\InventarioMovimientoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'inventario_movimientos')]
#[ORM\Entity(repositoryClass: InventarioMovimientoRepository::class)]
#[ORM\HasLifecycleCallbacks]
class InventarioMovimiento
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Inventario $inventario;

    #[ORM\Column(length: 30)]
    private string $tipo;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $cantidadAnterior;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $cantidadNueva;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $cantidadDelta;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $costoAnterior;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $costoNuevo;

    #[ORM\Column(length: 150)]
    private string $usuario;

    public function __construct(
        Inventario $inventario,
        string $tipo,
        float $cantidadAnterior,
        float $cantidadNueva,
        float $cantidadDelta,
        float $costoAnterior,
        float $costoNuevo,
        string $usuario
    ) {
        $this->inventario = $inventario;
        $this->tipo = $tipo;
        $this->cantidadAnterior = (string) $cantidadAnterior;
        $this->cantidadNueva = (string) $cantidadNueva;
        $this->cantidadDelta = (string) $cantidadDelta;
        $this->costoAnterior = (string) $costoAnterior;
        $this->costoNuevo = (string) $costoNuevo;
        $this->usuario = $usuario;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'inventarioId' => $this->inventario->getId(),
            'inventarioNombre' => $this->inventario->getNombre(),
            'tipo' => $this->tipo,
            'cantidadAnterior' => (float) $this->cantidadAnterior,
            'cantidadNueva' => (float) $this->cantidadNueva,
            'cantidadDelta' => (float) $this->cantidadDelta,
            'costoAnterior' => (float) $this->costoAnterior,
            'costoNuevo' => (float) $this->costoNuevo,
            'usuario' => $this->usuario,
            'fecha' => $this->getFechaCreacion()?->format('Y-m-d H:i:s'),
        ];
    }
}
