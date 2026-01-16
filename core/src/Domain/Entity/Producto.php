<?php

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Infrastructure\Doctrine\Repository\ProductoRepository;

#[ORM\Table(name: 'productos')]
#[ORM\Entity(repositoryClass: ProductoRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Producto
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $nombre;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $precioActual;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $costoActual;

    #[ORM\Column]
    private bool $activo = true;
}
