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
        // Buscamos los tiquetes ocupados por pedidos activos
        $qb = $this->createQueryBuilder('f');
        $ocupados = $qb->select('f.numeroTicket')
            ->where('f.estado IN (:estados)')
            ->setParameter('estados', [Factura::ESTADO_PENDIENTE, Factura::ESTADO_TERMINADO])
            ->getQuery()
            ->getScalarResult();

        $tiquetesOcupados = array_column($ocupados, 'numeroTicket');

        // Buscamos el primer número disponible del 1 al 20
        for ($i = 1; $i <= 20; $i++) {
            if (!in_array($i, $tiquetesOcupados)) {
                return $i;
            }
        }

        // Si todos están ocupados (raro), buscamos el máximo y seguimos (o reciclamos)
        return 1; 
    }
}
