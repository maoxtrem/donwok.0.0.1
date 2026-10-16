<?php

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'deducciones_caja')]
class DeduccionCaja
{
    public const TIPO_PORCENTAJE = 'PORCENTAJE';
    public const TIPO_VALOR = 'VALOR';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DatosEmpresa::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private DatosEmpresa $empresa;

    #[ORM\Column(length: 150)]
    private string $nombre;

    #[ORM\Column(length: 12)]
    private string $tipo;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private float $valor;

    #[ORM\Column(options: ['default' => true])]
    private bool $activo = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $cadaCierre = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $mostrarEnCierre = true;

    public function __construct(DatosEmpresa $empresa, string $nombre, string $tipo, float $valor, bool $activo = true, bool $cadaCierre = true, bool $mostrarEnCierre = true)
    {
        $this->empresa = $empresa;
        $this->actualizar($nombre, $tipo, $valor);
        $this->activo = $activo;
        $this->cadaCierre = $cadaCierre;
        $this->mostrarEnCierre = $mostrarEnCierre;
    }

    public function getId(): ?int { return $this->id; }
    public function getEmpresa(): DatosEmpresa { return $this->empresa; }
    public function getNombre(): string { return $this->nombre; }
    public function getTipo(): string { return $this->tipo; }
    public function getValor(): float { return $this->valor; }
    public function isActivo(): bool { return $this->activo; }
    public function isCadaCierre(): bool { return $this->cadaCierre; }
    public function isMostrarEnCierre(): bool { return $this->mostrarEnCierre; }

    public function actualizar(string $nombre, string $tipo, float $valor): void
    {
        $nombre = trim($nombre);
        $tipo = strtoupper(trim($tipo));

        if ($nombre === '') {
            throw new \InvalidArgumentException('El nombre de la deducción es obligatorio.');
        }
        if (!in_array($tipo, [self::TIPO_PORCENTAJE, self::TIPO_VALOR], true)) {
            throw new \InvalidArgumentException('El tipo de deducción no es válido.');
        }
        if ($valor < 0 || ($tipo === self::TIPO_PORCENTAJE && $valor > 100)) {
            throw new \InvalidArgumentException('El valor de la deducción no es válido.');
        }

        $this->nombre = $nombre;
        $this->tipo = $tipo;
        $this->valor = $valor;
    }

    public function setActivo(bool $activo): void { $this->activo = $activo; }
    public function setCadaCierre(bool $cadaCierre): void { $this->cadaCierre = $cadaCierre; }
    public function setMostrarEnCierre(bool $mostrarEnCierre): void { $this->mostrarEnCierre = $mostrarEnCierre; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'valor' => $this->valor,
            'activo' => $this->activo,
            'cadaCierre' => $this->cadaCierre,
            'mostrarEnCierre' => $this->mostrarEnCierre,
        ];
    }
}
