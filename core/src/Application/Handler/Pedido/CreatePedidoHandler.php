<?php

namespace App\Application\Handler\Pedido;

use App\Application\DTO\Request\PedidoRequestDTO;
use App\Domain\Entity\Factura;
use App\Domain\Repository\ProductoRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class CreatePedidoHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductoRepositoryInterface $productoRepo
    ) {}

    public function handle(PedidoRequestDTO $dto): Factura
    {
        $factura = new Factura();

        foreach ($dto->items as $itemData) {
            $producto = $this->productoRepo->buscarPorId($itemData['id']);
            if ($producto) {
                $factura->agregarItem(
                    $producto->getNombre(),
                    $producto->getPrecioActual(),
                    $producto->getCostoActual(),
                    $itemData['qty']
                );
            }
        }

        $this->em->persist($factura);
        $this->em->flush();
        
        return $factura;
    }
}
