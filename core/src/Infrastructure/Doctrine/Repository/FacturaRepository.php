<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Factura;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Factura>
 */
class FacturaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Factura::class);
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
