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
        $lastFactura = $this->createQueryBuilder('f')
            ->where('f.numeroFactura IS NOT NULL')
            ->orderBy('f.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastFactura) return "#000001";

        $lastNumber = (int) str_replace('#', '', $lastFactura->getNumeroFactura());
        return "#" . str_pad((string)($lastNumber + 1), 6, '0', STR_PAD_LEFT);
    }
}