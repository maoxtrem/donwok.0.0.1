<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\InventarioRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'inventarios')]
#[ORM\Entity(repositoryClass: InventarioRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Inventario
{
    use \App\Domain\Entity\Traits\FechasTrait;

    private const UNIDADES_MEDIDA_VALIDAS = ['gramo', 'kilo', 'libra', 'unidad'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $nombre;

    #[ORM\Column(length: 20)]
    private string $unidadMedida;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $cantidad;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $costoPromedio;

    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $valorTotalActual;

    public function __construct(string $nombre, string $unidadMedida, float $cantidad, float $costo)
    {
        if (empty(trim($nombre))) {
            throw new \InvalidArgumentException('El nombre no puede estar vacio.');
        }
        if (!in_array($unidadMedida, self::UNIDADES_MEDIDA_VALIDAS, true)) {
            throw new \InvalidArgumentException('Unidad de medida no valida.');
        }
        if ($cantidad < 0) {
            throw new \InvalidArgumentException('La cantidad no puede ser negativa.');
        }
        if ($costo < 0) {
            throw new \InvalidArgumentException('El costo no puede ser negativo.');
        }

        $this->nombre = trim($nombre);
        $this->unidadMedida = $unidadMedida;
        $this->cantidad = (string) $cantidad;
        $this->costoPromedio = (string) $costo;
        $this->recalcularValorTotalActual();
    }

    public function getId(): ?int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
    public function getUnidadMedida(): string { return $this->unidadMedida; }
    public function getCantidad(): float { return (float) $this->cantidad; }
    public function getCostoPromedio(): float { return (float) $this->costoPromedio; }
    public function getValorTotalActual(): float { return (float) $this->valorTotalActual; }

    public function actualizarCantidad(float $cantidadMovimiento, ?float $costoNuevo = null): void
    {
        if ($cantidadMovimiento <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor que 0.');
        }

        $cantidadActual = (float) $this->cantidad;
        $costoActual = (float) $this->costoPromedio;

        if ($costoNuevo === null) {
            $nuevaCantidad = $cantidadActual - $cantidadMovimiento;
            if ($nuevaCantidad < 0) {
                throw new \InvalidArgumentException('No hay suficiente inventario para descontar esa cantidad.');
            }
            $this->cantidad = (string) $nuevaCantidad;
            $this->recalcularValorTotalActual();
            return;
        }

        if ($costoNuevo < 0) {
            throw new \InvalidArgumentException('El costo nuevo no puede ser negativo.');
        }

        $nuevaCantidad = $cantidadActual + $cantidadMovimiento;
        if ($nuevaCantidad <= 0) {
            throw new \InvalidArgumentException('La nueva cantidad no es valida.');
        }

        $costoTotalActual = $cantidadActual * $costoActual;
        $costoTotalNuevoIngreso = $cantidadMovimiento * $costoNuevo;
        $nuevoCostoPromedio = ($costoTotalActual + $costoTotalNuevoIngreso) / $nuevaCantidad;

        $this->cantidad = (string) $nuevaCantidad;
        $this->costoPromedio = (string) $nuevoCostoPromedio;
        $this->recalcularValorTotalActual();
    }

    public function recalcularValorTotalActual(): void
    {
        $this->valorTotalActual = (string) ((float) $this->cantidad * (float) $this->costoPromedio);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'unidadMedida' => $this->unidadMedida,
            'cantidad' => (float) $this->cantidad,
            'costoPromedio' => (float) $this->costoPromedio,
            'valorTotalActual' => (float) $this->valorTotalActual,
        ];
    }
}
