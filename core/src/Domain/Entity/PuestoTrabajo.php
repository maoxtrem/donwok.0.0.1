<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\PuestoTrabajoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PuestoTrabajoRepository::class)]
#[ORM\Table(name: 'puestos_trabajo')]
#[ORM\HasLifecycleCallbacks]
class PuestoTrabajo
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $titulo;

    #[ORM\Column(length: 30, unique: true)]
    private string $codigoPuesto;

    #[ORM\Column(length: 64)]
    private string $clavePuestoHash;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descripcion;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $ubicacion;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $tipoContrato;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?float $salario;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?float $valorHora;

    #[ORM\Column(type: 'integer', options: ['default' => 50])]
    private int $denominacionMinima = 50;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $horaInicio;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $horaCierre;

    #[ORM\Column(type: 'json')]
    private array $diasActivos = [];

    #[ORM\Column(options: ['default' => true])]
    private bool $activo = true;

    public function __construct(
        string $titulo,
        string $codigoPuesto,
        string $clavePuesto,
        ?string $descripcion = null,
        ?string $ubicacion = null,
        ?string $tipoContrato = null,
        ?float $salario = null,
        ?float $valorHora = null,
        int $denominacionMinima = 50,
        ?string $horaInicio = null,
        ?string $horaCierre = null,
        array $diasActivos = []
    ) {
        $titulo = trim($titulo);
        if ($titulo === '' || mb_strlen($titulo) > 150) {
            throw new \InvalidArgumentException('El título del puesto es obligatorio.');
        }
        if ($salario !== null && $salario < 0) {
            throw new \InvalidArgumentException('El salario no puede ser negativo.');
        }

        $this->titulo = $titulo;
        $this->codigoPuesto = $this->validarCodigo($codigoPuesto);
        $this->clavePuestoHash = hash('sha256', $this->normalizarClave($clavePuesto));
        $this->descripcion = $descripcion ? trim($descripcion) : null;
        $this->ubicacion = $ubicacion ? trim($ubicacion) : null;
        $this->tipoContrato = $tipoContrato ? trim($tipoContrato) : null;
        $this->salario = $salario !== null ? round($salario, 2) : null;
        if ($valorHora !== null && $valorHora < 0) {
            throw new \InvalidArgumentException('El valor de la hora no puede ser negativo.');
        }
        $this->valorHora = $valorHora !== null ? round($valorHora, 2) : null;
        if ($denominacionMinima <= 0) {
            throw new \InvalidArgumentException('La denominación mínima debe ser mayor que cero.');
        }
        $this->denominacionMinima = $denominacionMinima;
        $this->horaInicio = $this->parseHora($horaInicio);
        $this->horaCierre = $this->parseHora($horaCierre);
        $this->diasActivos = array_values(array_intersect(
            ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'],
            array_map(static fn($dia) => mb_strtolower(trim((string)$dia)), $diasActivos)
        ));
    }

    public function getId(): ?int { return $this->id; }
    public function getTitulo(): string { return $this->titulo; }
    public function getCodigoPuesto(): string { return $this->codigoPuesto; }
    public function getDescripcion(): ?string { return $this->descripcion; }
    public function getUbicacion(): ?string { return $this->ubicacion; }
    public function getTipoContrato(): ?string { return $this->tipoContrato; }
    public function getSalario(): ?float { return $this->salario; }
    public function getValorHora(): ?float { return $this->valorHora; }
    public function getDenominacionMinima(): int { return $this->denominacionMinima; }
    public function getHoraInicio(): ?\DateTimeInterface { return $this->horaInicio; }
    public function getHoraCierre(): ?\DateTimeInterface { return $this->horaCierre; }
    public function getDiasActivos(): array { return $this->diasActivos; }
    public function estaActivo(): bool { return $this->activo; }

    public function actualizar(
        string $titulo,
        string $codigoPuesto,
        ?string $clavePuesto,
        ?string $descripcion,
        ?float $valorHora,
        int $denominacionMinima,
        array $diasActivos
    ): void {
        $titulo = trim($titulo);
        if ($titulo === '' || mb_strlen($titulo) > 150) {
            throw new \InvalidArgumentException('El título del puesto es obligatorio.');
        }
        if ($valorHora !== null && $valorHora < 0) {
            throw new \InvalidArgumentException('El valor de la hora no puede ser negativo.');
        }
        if ($denominacionMinima <= 0) {
            throw new \InvalidArgumentException('La denominación mínima debe ser mayor que cero.');
        }

        $this->titulo = $titulo;
        $this->codigoPuesto = $this->validarCodigo($codigoPuesto);
        if ($clavePuesto !== null && trim($clavePuesto) !== '') {
            $this->clavePuestoHash = hash('sha256', $this->normalizarClave($clavePuesto));
        }
        $this->descripcion = $descripcion ? trim($descripcion) : null;
        $this->valorHora = $valorHora !== null ? round($valorHora, 2) : null;
        $this->denominacionMinima = $denominacionMinima;
        $this->diasActivos = array_values(array_intersect(
            ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'],
            array_map(static fn($dia) => mb_strtolower(trim((string)$dia)), $diasActivos)
        ));
    }

    public function cambiarEstado(bool $activo): void { $this->activo = $activo; }
    public function claveValida(string $clave): bool
    {
        return hash_equals($this->clavePuestoHash, hash('sha256', $this->normalizarClave($clave)));
    }

    private function parseHora(?string $hora): ?\DateTimeInterface
    {
        if (!$hora) {
            return null;
        }
        $resultado = \DateTime::createFromFormat('!H:i', $hora);
        if (!$resultado || \DateTime::getLastErrors() !== false) {
            throw new \InvalidArgumentException('La hora debe tener el formato HH:MM.');
        }
        return $resultado;
    }

    private function validarCodigo(string $codigo): string
    {
        $codigo = strtoupper(trim($codigo));
        if ($codigo === '' || mb_strlen($codigo) > 30) {
            throw new \InvalidArgumentException('El código del puesto es obligatorio.');
        }
        return $codigo;
    }

    private function normalizarClave(string $clave): string
    {
        return strtoupper(trim($clave));
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'codigoPuesto' => $this->codigoPuesto,
            'descripcion' => $this->descripcion,
            'ubicacion' => $this->ubicacion,
            'tipoContrato' => $this->tipoContrato,
            'salario' => $this->salario,
            'valorHora' => $this->valorHora,
            'denominacionMinima' => $this->denominacionMinima,
            'horaInicio' => $this->horaInicio?->format('H:i'),
            'horaCierre' => $this->horaCierre?->format('H:i'),
            'estadoHorario' => $this->horaCierre ? 'Cerrado' : 'Pendiente de cierre',
            'diasActivos' => $this->diasActivos,
            'activo' => $this->activo,
            'fechaCreacion' => $this->getFechaCreacion()->format('Y-m-d H:i:s'),
        ];
    }
}
