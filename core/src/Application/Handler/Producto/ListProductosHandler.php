<?php
// src/Application/Handler/Producto/ListProductosHandler.php
namespace App\Application\Handler\Producto;


use App\Domain\Repository\ProductoRepositoryInterface;
use App\Domain\Entity\Producto;
use App\Application\Assembler\ProductoResponseAssembler;

class ListProductosHandler
{
    public function __construct(private ProductoRepositoryInterface $repo) {}

    public function handle(): array
    {
        return array_map(
            fn(Producto $p) => ProductoResponseAssembler::fromEntity($p),
            $this->repo->buscarTodos()
        );
    }
}
