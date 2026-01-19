<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\FacturaDetalleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FacturaDetalleRepository::class)]
#[ORM\Table(name: 'factura_detalles')]
#[ORM\HasLifecycleCallbacks]
class FacturaDetalle
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Factura $factura;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Producto $producto;

    #[ORM\Column(length: 150)]
    private string $nombreProducto;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $precioVenta;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $costoUnitario;

    #[ORM\Column]
    private int $cantidad;
}