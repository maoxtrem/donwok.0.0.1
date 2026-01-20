<?php

namespace App\Application\Handler\Pedido;

use App\Application\DTO\Request\PedidoRequestDTO;
use App\Domain\Entity\Pedido;
use App\Domain\Repository\ProductoRepositoryInterface;
use App\Domain\Repository\PedidoRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class CreatePedidoHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductoRepositoryInterface $productoRepo,
        private PedidoRepositoryInterface $pedidoRepo
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
        
        // 🟢 NOTIFICACIÓN TIEMPO REAL
        $this->notifyMercure($pedido);
        
        return $pedido;
    }

    private function notifyMercure(Pedido $pedido): void
    {
        $url = "http://mercure/.well-known/mercure";
        $data = http_build_query([
            'topic' => 'donwok/pedidos',
            'data'  => json_encode(['type' => 'NEW_ORDER', 'pedido' => $pedido->toArray()])
        ]);

        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n" .
                             "Authorization: Bearer !ChangeThisMercureHubFreeApp2022!\r\n",
                'content' => $data,
                'timeout' => 2
            ]
        ];

        try {
            file_get_contents($url, false, stream_context_create($opts));
        } catch (\Exception $e) {
            // Silencioso si falla el tiempo real para no bloquear el negocio
        }
    }
}
