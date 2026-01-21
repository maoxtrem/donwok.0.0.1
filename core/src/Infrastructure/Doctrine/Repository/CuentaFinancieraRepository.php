<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\CuentaFinanciera;
use App\Domain\Repository\CuentaFinancieraRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CuentaFinanciera>
 */
class CuentaFinancieraRepository extends ServiceEntityRepository implements CuentaFinancieraRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CuentaFinanciera::class);
    }

    public function buscarPorId(int $id): ?CuentaFinanciera
    {
        return $this->find($id);
    }

    public function buscarPorTipo(string $tipo): ?CuentaFinanciera
    {
        return $this->findOneBy(['tipo' => $tipo]);
    }

    public function buscarPorNombre(string $nombre): ?CuentaFinanciera
    {
        return $this->findOneBy(['nombre' => $nombre]);
    }

    public function listarTodas(): array
    {
        return $this->findAll();
    }

    public function obtenerSaldosActuales(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                c.id, 
                c.nombre, 
                c.saldo_inicial,
                IFNULL(SUM(CASE WHEN m.tipo_movimiento = 'INGRESO' THEN m.monto ELSE -m.monto END), 0) as flujo_movimientos
            FROM cuenta_financiera c
            LEFT JOIN movimientos_financieros m ON m.cuenta_financiera_id = c.id
            GROUP BY c.id, c.nombre, c.saldo_inicial
        ";

        $results = $conn->fetchAllAssociative($sql);
        
        return array_map(function($row) {
            return [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'saldo' => (float)$row['saldo_inicial'] + (float)$row['flujo_movimientos']
            ];
        }, $results);
    }

    //    /**
    //     * @return CuentaFinanciera[] Returns an array of CuentaFinanciera objects
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

    //    public function findOneBySomeField($value): ?CuentaFinanciera
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
