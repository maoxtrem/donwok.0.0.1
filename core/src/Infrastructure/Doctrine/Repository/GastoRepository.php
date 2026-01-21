<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Gasto;
use App\Domain\Repository\GastoRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Gasto>
 */
class GastoRepository extends ServiceEntityRepository implements GastoRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gasto::class);
    }

    public function guardar(Gasto $gasto): void
    {
        $this->getEntityManager()->persist($gasto);
        $this->getEntityManager()->flush();
    }

    public function buscarPorId(int $id): ?Gasto
    {
        return $this->find($id);
    }

    public function eliminar(Gasto $gasto): void
    {
        $this->getEntityManager()->remove($gasto);
        $this->getEntityManager()->flush();
    }

    public function findPendientesCierre(): array
    {
        return $this->findBy(['isCerrado' => false]);
    }

    //    /**
    //     * @return Gasto[] Returns an array of Gasto objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('g.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Gasto
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
