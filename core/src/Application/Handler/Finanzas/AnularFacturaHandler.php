<?php

namespace App\Application\Handler\Finanzas;

use App\Domain\Repository\FacturaRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class AnularFacturaHandler
{
    public function __construct(
        private FacturaRepositoryInterface $facturaRepo,
        private EntityManagerInterface $em
    ) {}

    public function handle(int $id): void
    {
        $factura = $this->facturaRepo->buscarPorId($id);
        
        if (!$factura) {
            throw new \Exception("Factura no encontrada.");
        }

        $factura->anular();
        $this->em->flush();
    }
}
