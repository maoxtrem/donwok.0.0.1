<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Venta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Venta>
 */
class VentaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Venta::class);
    }

    public function getNextInvoiceNumber(): string
    {
        // Buscamos el último número de factura generado
        $lastVenta = $this->createQueryBuilder('v')
            ->where('v.numeroFactura IS NOT NULL')
            ->orderBy('v.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastVenta) {
            return "#000001";
        }

        // Extraemos el número después del #
        $lastNumber = (int) str_replace('#', '', $lastVenta->getNumeroFactura());
        $nextNumber = $lastNumber + 1;

        // Formateamos con 6 dígitos
        return "#" . str_pad((string)$nextNumber, 6, '0', STR_PAD_LEFT);
    }
}