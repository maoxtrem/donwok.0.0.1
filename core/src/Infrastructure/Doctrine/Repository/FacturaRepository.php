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
        return $this->findBy(['estado' => Factura::ESTADO_FACTURADO]);
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

    public function getNextTicketNumber(): int
    {
        $ahora = new \DateTimeImmutable();
        $inicioDia = $ahora->setTime(0, 0, 0);
        $finDia = $ahora->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('f');
        $lastTicket = $qb->select('MAX(f.numeroTicket)')
            ->where('f.fechaCreacion >= :inicioDia')
            ->andWhere('f.fechaCreacion <= :finDia')
            ->setParameter('inicioDia', $inicioDia)
            ->setParameter('finDia', $finDia)
            ->getQuery()
            ->getSingleScalarResult();

        if (!$lastTicket) {
            return 1;
        }

        $nextTicket = (int)$lastTicket + 1;
        if ($nextTicket > 20) {
            return 1;
        }

        return $nextTicket;
    }
}
