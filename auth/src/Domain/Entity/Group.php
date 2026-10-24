<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\GroupRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: 'grupos')]
class Group
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $nombre;

    #[ORM\Column(type: 'json')]
    private array $urls = [];

    #[ORM\Column(name: 'url_inicio', length: 255, nullable: true)]
    private ?string $urlInicio = null;

    public function __construct(string $nombre, array $urls = [], ?string $urlInicio = null)
    {
        $this->actualizar($nombre, $urls, $urlInicio);
    }

    public function actualizar(string $nombre, array $urls, ?string $urlInicio = null): void
    {
        $nombre = trim($nombre);
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            throw new \InvalidArgumentException('El nombre del grupo es obligatorio.');
        }
        $this->nombre = $nombre;
        $this->urls = array_values(array_unique(array_filter(array_map('strval', $urls))));
        $urlInicio = $urlInicio !== null ? trim($urlInicio) : null;
        $this->urlInicio = ($urlInicio && in_array($urlInicio, $this->urls, true)) ? $urlInicio : null;
    }

    public function getId(): ?int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
    public function getUrls(): array { return $this->urls; }
    public function getUrlInicio(): ?string { return $this->urlInicio; }
    public function toArray(): array { return ['id' => $this->id, 'nombre' => $this->nombre, 'urls' => $this->urls, 'url_inicio' => $this->urlInicio]; }
}
