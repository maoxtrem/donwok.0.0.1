<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Pedido;

interface PedidoRepositoryInterface
{
    public function guardar(Pedido $pedido): void;
    
    public function buscarPorId(int $id): ?Pedido;

    /**
     * @return Pedido[]
     */
    public function findPendientes(): array;
}
