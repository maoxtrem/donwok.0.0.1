<?php

namespace App\Application\Handler\Pedido;

use App\Application\DTO\Request\PedidoRequestDTO;
use App\Domain\Entity\Venta;
use App\Domain\Repository\ProductoRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class CreatePedidoHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductoRepositoryInterface $productoRepo
    ) {}

    public function handle(PedidoRequestDTO $dto): Venta
    {
        $venta = new Venta();

        foreach ($dto->items as $itemData) {
            $producto = $this->productoRepo->buscarPorId($itemData['id']);
            if ($producto) {
                // 🟢 Clonamos la info del catálogo hacia la venta (Snapshot)
                $venta->agregarItem(
                    $producto->getNombre(),
                    $producto->getPrecioActual(),
                    $producto->getCostoActual(),
                    $itemData['qty']
                );
            }
        }

        $this->em->persist($venta);
        $this->em->flush();
        
        return $venta;
    }
}