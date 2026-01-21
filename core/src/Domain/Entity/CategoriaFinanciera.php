<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\CategoriaFinancieraRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoriaFinancieraRepository::class)]
#[ORM\Table(name: 'categorias_financieras')]
#[ORM\HasLifecycleCallbacks]
class CategoriaFinanciera
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nombre;

    #[ORM\Column(length: 10)]
    private string $tipo; // INGRESO | EGRESO | MIXTO

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $descripcion = null;

    public function __construct(string $nombre, string $tipo, ?string $descripcion = null)
    {
        $this->nombre = $nombre;
        $this->tipo = $tipo;
        $this->descripcion = $descripcion;
    }

    public function getId(): ?int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
    public function getTipo(): string { return $this->tipo; }
    public function getDescripcion(): ?string { return $this->descripcion; }
}