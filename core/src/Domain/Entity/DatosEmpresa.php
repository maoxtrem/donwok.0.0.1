<?php

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'datos_empresa')]
class DatosEmpresa
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $nombre = '';

    #[ORM\Column(length: 50)]
    private string $nit = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fraseDia = null;

    #[ORM\Column(length: 50)]
    private string $telefonoDomicilios = '';

    #[ORM\Column(options: ['default' => true])]
    private bool $impresoraActiva = true;

    public function getId(): ?int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function getNit(): string { return $this->nit; }
    public function setNit(string $nit): void { $this->nit = $nit; }
    public function getFraseDia(): ?string { return $this->fraseDia; }
    public function setFraseDia(?string $fraseDia): void { $this->fraseDia = $fraseDia; }
    public function getTelefonoDomicilios(): string { return $this->telefonoDomicilios; }
    public function setTelefonoDomicilios(string $telefono): void { $this->telefonoDomicilios = $telefono; }
    public function isImpresoraActiva(): bool { return $this->impresoraActiva; }
    public function setImpresoraActiva(bool $activa): void { $this->impresoraActiva = $activa; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'nit' => $this->nit,
            'fraseDia' => $this->fraseDia ?? '',
            'telefonoDomicilios' => $this->telefonoDomicilios,
            'impresoraActiva' => $this->impresoraActiva,
        ];
    }
}
