<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\CategoriaFinanciera;
use App\Domain\Repository\CategoriaFinancieraRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoriaFinanciera>
 */
class CategoriaFinancieraRepository extends ServiceEntityRepository implements CategoriaFinancieraRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoriaFinanciera::class);
    }

    public function buscarPorId(int $id): ?CategoriaFinanciera
    {
        return $this->find($id);
    }

    public function buscarPorNombre(string $nombre): ?CategoriaFinanciera
    {
        return $this->findOneBy(['nombre' => $nombre]);
    }

    public function listarTodas(): array
    {
        return $this->findAll();
    }

    //    /**
    //     * @return CategoriaFinanciera[] Returns an array of CategoriaFinanciera objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CategoriaFinanciera
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
