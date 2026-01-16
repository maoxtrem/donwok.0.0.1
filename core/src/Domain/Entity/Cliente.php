<?php

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\ClienteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClienteRepository::class)]
#[ORM\Table(name: 'clientes')]
#[ORM\HasLifecycleCallbacks]
class Cliente
{
    use \App\Domain\Entity\Traits\FechasTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $nombre;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $email = null;
}
