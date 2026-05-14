<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\InventarioMovimiento;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InventarioMovimiento>
 */
class InventarioMovimientoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventarioMovimiento::class);
    }

    /** @return InventarioMovimiento[] */
    public function listarRecientes(int $limite = 200): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.fechaCreacion', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }
}
