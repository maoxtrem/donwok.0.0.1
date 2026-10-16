<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Factura;
use App\Domain\Entity\FacturaDetalle;
use App\Domain\Repository\FacturaDetalleRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FacturaDetalle>
 */
class FacturaDetalleRepository extends ServiceEntityRepository implements FacturaDetalleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FacturaDetalle::class);
    }

    public function obtenerRentabilidadPorProducto(\DateTimeInterface $desde, \DateTimeInterface $hasta): array
    {
        $qb = $this->createQueryBuilder('fd');
        
        $fechaDesde = (clone $desde)->setTime(0, 0, 0);
        $fechaHasta = (clone $hasta)->setTime(23, 59, 59);

        return $qb->select('fd.nombreProducto as nombre')
            ->addSelect('SUM(fd.cantidad) as cantidad')
            ->addSelect('SUM(fd.precioUnitario * fd.cantidad) as total_venta')
            ->addSelect('SUM(fd.costoUnitario * fd.cantidad) as total_costo')
            ->addSelect('SUM((fd.precioUnitario - fd.costoUnitario) * fd.cantidad) as ganancia')
            ->innerJoin('fd.factura', 'f')
            ->where('f.fechaCreacion >= :desde')
            ->andWhere('f.fechaCreacion <= :hasta')
            ->andWhere('f.estado != :anulada')
            ->setParameter('desde', $fechaDesde)
            ->setParameter('hasta', $fechaHasta)
            ->setParameter('anulada', Factura::ESTADO_ANULADA)
            ->groupBy('nombre')
            ->orderBy('ganancia', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function obtenerTotalesPorFecha(\DateTimeInterface $desde, \DateTimeInterface $hasta): array
    {
        $fechaDesde = (clone $desde)->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $fechaHasta = (clone $hasta)->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        $sql = <<<SQL
            SELECT
                DATE(f.fecha_creacion) AS fecha,
                SUM(fd.precio_unitario * fd.cantidad) AS total_venta,
                SUM(fd.costo_unitario * fd.cantidad) AS total_costo
            FROM factura_detalles fd
            INNER JOIN facturas f ON f.id = fd.factura_id
            WHERE f.fecha_creacion >= :desde
              AND f.fecha_creacion <= :hasta
              AND f.estado != :anulada
            GROUP BY DATE(f.fecha_creacion)
            ORDER BY fecha ASC
        SQL;

        return $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, [
            'desde' => $fechaDesde,
            'hasta' => $fechaHasta,
            'anulada' => Factura::ESTADO_ANULADA,
        ]);
    }

    //    /**
    //     * @return FacturaDetalle[] Returns an array of FacturaDetalle objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?FacturaDetalle
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
