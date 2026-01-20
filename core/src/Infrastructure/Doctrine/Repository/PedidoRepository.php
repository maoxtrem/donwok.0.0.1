<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Pedido;
use App\Domain\Repository\PedidoRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pedido>
 */
class PedidoRepository extends ServiceEntityRepository implements PedidoRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pedido::class);
    }

    public function guardar(Pedido $pedido): void
    {
        $this->getEntityManager()->persist($pedido);
        $this->getEntityManager()->flush();
    }

    public function buscarPorId(int $id): ?Pedido
    {
        return $this->find($id);
    }

    public function findPendientes(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.estado = :val')
            ->setParameter('val', Pedido::ESTADO_PENDIENTE)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Pedido[] Returns an array of Pedido objects
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

    //    public function findOneBySomeField($value): ?Pedido
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
