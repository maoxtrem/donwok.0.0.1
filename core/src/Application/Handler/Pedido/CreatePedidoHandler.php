<?php

namespace App\Application\Handler\Pedido;

use App\Application\DTO\Request\PedidoRequestDTO;
use App\Domain\Entity\Pedido;
use App\Domain\Repository\ProductoRepositoryInterface;
use App\Infrastructure\Doctrine\Repository\PedidoRepository;
use Doctrine\ORM\EntityManagerInterface;

class CreatePedidoHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductoRepositoryInterface $productoRepo,
        private PedidoRepository $pedidoRepo
    ) {}

    public function handle(PedidoRequestDTO $dto): Pedido
    {
        $pedido = new Pedido();

        foreach ($dto->items as $itemData) {
            $producto = $this->productoRepo->buscarPorId($itemData['id']);
            if ($producto) {
                $pedido->addItem($producto, $itemData['qty']);
            }
        }

        $this->pedidoRepo->guardar($pedido);
        
        return $pedido;
    }
}
