<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Prestamo;
use App\Domain\Repository\PrestamoRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prestamo>
 */
class PrestamoRepository extends ServiceEntityRepository implements PrestamoRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prestamo::class);
    }

    public function guardar(Prestamo $prestamo): void
    {
        $this->getEntityManager()->persist($prestamo);
        $this->getEntityManager()->flush();
    }

    public function buscarPorId(int $id): ?Prestamo
    {
        return $this->find($id);
    }

    public function buscarActivos(): array
    {
        return $this->findBy(['estado' => 'PENDIENTE'], ['fechaInicio' => 'DESC']);
    }

    public function eliminar(Prestamo $prestamo): void
    {
        $this->getEntityManager()->remove($prestamo);
        $this->getEntityManager()->flush();
    }

    public function findPendientesCierre(): array
    {
        return $this->findBy(['isCerrado' => false, 'tipo' => 'OTORGADO']);
    }

    //    /**
    //     * @return Prestamo[] Returns an array of Prestamo objects
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

    //    public function findOneBySomeField($value): ?Prestamo
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
