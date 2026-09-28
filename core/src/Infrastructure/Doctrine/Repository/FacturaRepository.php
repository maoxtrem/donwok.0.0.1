<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Factura;
use App\Domain\Repository\FacturaRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Factura>
 */
class FacturaRepository extends ServiceEntityRepository implements FacturaRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Factura::class);
    }

    public function guardar(Factura $factura): void
    {
        $this->getEntityManager()->persist($factura);
        $this->getEntityManager()->flush();
    }

    public function buscarPorId(int $id): ?Factura
    {
        return $this->find($id);
    }

    /**
     * @return Factura[]
     */
    public function findPendientesCierre(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.numeroFactura IS NOT NULL')
            ->andWhere('f.estado NOT IN (:estadosNoCobrables)')
            ->setParameter('estadosNoCobrables', [Factura::ESTADO_ANULADA, Factura::ESTADO_CERRADA])
            ->orderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextInvoiceNumber(): string
    {
        // 🟢 Buscamos el valor máximo del campo numero_factura de forma más robusta
        $qb = $this->createQueryBuilder('f');
        $lastFactura = $qb->select('f.numeroFactura')
            ->where('f.numeroFactura IS NOT NULL')
            ->orderBy('f.numeroFactura', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastFactura) {
            return "#000001";
        }

        // Extraemos el número, lo incrementamos y rellenamos con ceros
        $currentNumberStr = $lastFactura['numeroFactura'];
        $lastNumber = (int) str_replace('#', '', $currentNumberStr);
        $nextNumber = $lastNumber + 1;

        return "#" . str_pad((string)$nextNumber, 6, '0', STR_PAD_LEFT);
    }

}
