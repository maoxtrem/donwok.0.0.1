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

    public function listarActivas(): array
    {
        return $this->findBy(['activa' => true], ['nombre' => 'ASC']);
    }

    public function listarParaFacturacion(): array
    {
        return $this->findBy(['activa' => true, 'usarEnFacturacion' => true], ['nombre' => 'ASC']);
    }

    public function listarParaEgresos(): array
    {
        return $this->findBy(['activa' => true, 'usarEnEgresos' => true], ['nombre' => 'ASC']);
    }

    public function listarParaCuentasPorPagar(): array
    {
        return $this->findBy(['activa' => true, 'usarEnCuentasPorPagar' => true], ['nombre' => 'ASC']);
    }

    public function obtenerSaldosActuales(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                c.id, 
                c.nombre, 
                c.tipo,
                c.saldo_inicial,
                c.activa,
                c.usar_en_facturacion,
                c.incluir_en_arqueo,
                c.mostrar_en_balance,
                c.usar_en_egresos,
                c.usar_en_cuentas_por_pagar,
                c.es_temporal,
                IFNULL(SUM(CASE WHEN m.tipo_movimiento = 'INGRESO' THEN m.monto ELSE -m.monto END), 0) as flujo_movimientos
            FROM cuenta_financiera c
            LEFT JOIN movimientos_financieros m ON m.cuenta_financiera_id = c.id
            GROUP BY c.id, c.nombre, c.saldo_inicial, c.activa, c.usar_en_facturacion, c.incluir_en_arqueo, c.mostrar_en_balance, c.usar_en_egresos, c.usar_en_cuentas_por_pagar, c.es_temporal
        ";

        $results = $conn->fetchAllAssociative($sql);
        
        return array_map(function($row) {
            return [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'tipo' => $row['tipo'],
                'activa' => (bool)$row['activa'],
                'usar_en_facturacion' => (bool)$row['usar_en_facturacion'],
                'incluir_en_arqueo' => (bool)$row['incluir_en_arqueo'],
                'mostrar_en_balance' => (bool)$row['mostrar_en_balance'],
                'usar_en_egresos' => (bool)$row['usar_en_egresos'],
                'usar_en_cuentas_por_pagar' => (bool)$row['usar_en_cuentas_por_pagar'],
                'es_temporal' => (bool)$row['es_temporal'],
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
