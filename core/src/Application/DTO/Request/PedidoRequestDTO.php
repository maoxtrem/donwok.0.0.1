<?php

namespace App\Application\DTO\Request;

class PedidoRequestDTO
{
    /**
     * @param array<array{id: int, qty: int}> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly bool $esPago = false,
        public readonly string $tipo = 'MESA'
    ) {}
}
