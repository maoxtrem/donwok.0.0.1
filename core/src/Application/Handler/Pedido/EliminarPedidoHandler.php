<?php

namespace App\Application\Handler\Pedido;

use App\Domain\Entity\Factura;
use App\Domain\Repository\FacturaRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class EliminarPedidoHandler
{
    public function __construct(
        private FacturaRepositoryInterface $facturaRepo,
        private EntityManagerInterface $em
    ) {}

    public function handle(int $id): void
    {
        $pedido = $this->facturaRepo->buscarPorId($id);
        
        if (!$pedido) {
            throw new \Exception("Pedido no encontrado.");
        }

        // Solo permitir eliminar si no ha sido facturado ni cerrado
        if (!in_array($pedido->getEstado(), [Factura::ESTADO_PENDIENTE, Factura::ESTADO_TERMINADO])) {
            throw new \Exception("No se puede eliminar un pedido que ya ha sido facturado o cerrado. Intente anularlo si es una factura.");
        }

        $this->em->remove($pedido);
        $this->em->flush();
    }
}
