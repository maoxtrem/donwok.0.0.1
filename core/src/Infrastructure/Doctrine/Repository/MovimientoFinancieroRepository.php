<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\MovimientoFinanciero;
use App\Domain\Repository\MovimientoFinancieroRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MovimientoFinanciero>
 */
class MovimientoFinancieroRepository extends ServiceEntityRepository implements MovimientoFinancieroRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovimientoFinanciero::class);
    }

    public function guardar(MovimientoFinanciero $movimiento): void
    {
        $this->getEntityManager()->persist($movimiento);
        $this->getEntityManager()->flush();
    }

    public function buscarRecientes(int $limite = 50): array
    {
        return $this->findBy([], ['fechaMovimiento' => 'DESC'], $limite);
    }

    public function buscarPaginados(int $pagina, int $limite, ?\DateTimeInterface $desde = null, ?\DateTimeInterface $hasta = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.fechaMovimiento', 'DESC')
            ->addOrderBy('m.id', 'DESC');

        if ($desde) {
            $qb->andWhere('m.fechaMovimiento >= :desde')
               ->setParameter('desde', $desde->setTime(0, 0, 0));
        }

        if ($hasta) {
            $qb->andWhere('m.fechaMovimiento <= :hasta')
               ->setParameter('hasta', $hasta->setTime(23, 59, 59));
        }

        // Clonar para contar el total antes de aplicar offset/limit
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();

        $items = $qb->setFirstResult(($pagina - 1) * $limite)
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total
        ];
    }

    public function obtenerTotalesPorPeriodo(\DateTimeInterface $desde, \DateTimeInterface $hasta): array
    {
        $fechaDesde = (clone $desde)->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $fechaHasta = (clone $hasta)->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        $sql = "
            SELECT 
                DATE(m.fecha_movimiento) as fecha,
                m.tipo_movimiento as tipo,
                SUM(m.monto) as total,
                c.nombre as categoria
            FROM movimientos_financieros m
            INNER JOIN categorias_financieras c ON m.categoria_financiera_id = c.id
            WHERE m.fecha_movimiento >= :desde AND m.fecha_movimiento <= :hasta
            GROUP BY DATE(m.fecha_movimiento), m.tipo_movimiento, c.nombre
            ORDER BY fecha ASC
        ";

        $conn = $this->getEntityManager()->getConnection();
        
        return $conn->fetchAllAssociative($sql, [
            'desde' => $fechaDesde,
            'hasta' => $fechaHasta
        ]);
    }

    //    /**
    //     * @return MovimientoFinanciero[] Returns an array of MovimientoFinanciero objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MovimientoFinanciero
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
