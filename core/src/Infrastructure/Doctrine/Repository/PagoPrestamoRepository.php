<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\PagoPrestamo;
use App\Domain\Repository\PagoPrestamoRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PagoPrestamo>
 */
class PagoPrestamoRepository extends ServiceEntityRepository implements PagoPrestamoRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PagoPrestamo::class);
    }

    public function findPendientesCierre(): array
    {
        return $this->findBy(['isCerrado' => false]);
    }

    //    /**
    //     * @return PagoPrestamo[] Returns an array of PagoPrestamo objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?PagoPrestamo
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
