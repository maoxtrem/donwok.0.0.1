<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Inventario;
use App\Domain\Repository\InventarioRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inventario>
 */
class InventarioRepository extends ServiceEntityRepository implements InventarioRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inventario::class);
    }

    public function guardar(Inventario $inventario): void
    {
        $this->getEntityManager()->persist($inventario);
        $this->getEntityManager()->flush();
    }

    public function buscarPorId(int $id): ?Inventario
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.id = :id')
            ->andWhere('i.fechaEliminacion IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function buscarPorNombre(string $nombre): ?Inventario
    {
        return $this->createQueryBuilder('i')
            ->andWhere('LOWER(i.nombre) = LOWER(:nombre)')
            ->andWhere('i.fechaEliminacion IS NULL')
            ->setParameter('nombre', trim($nombre))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function buscarTodos(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.fechaEliminacion IS NULL')
            ->orderBy('i.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function totalDineroInventario(): float
    {
        $resultado = $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.valorTotalActual), 0) as total')
            ->andWhere('i.fechaEliminacion IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $resultado;
    }
}
