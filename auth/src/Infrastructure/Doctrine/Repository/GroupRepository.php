<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Group;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Group::class); }
    public function buscarPorId(int $id): ?Group { return $this->find($id); }
    public function listar(): array { return $this->findBy([], ['nombre' => 'ASC']); }
}
