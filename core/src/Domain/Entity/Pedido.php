<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\PedidoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PedidoRepository::class)]
#[ORM\Table(name: 'pedidos')]
#[ORM\HasLifecycleCallbacks]
class Pedido
{
    use \App\Domain\Entity\Traits\FechasTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Factura $factura;

    #[ORM\Column(length: 20)]
    private string $estado;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fechaPedido;
}