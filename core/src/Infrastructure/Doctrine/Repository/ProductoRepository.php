<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Producto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<Producto>
 */
class ProductoRepository extends ServiceEntityRepository implements \App\Domain\Repository\ProductoRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Producto::class);
    }
    public function guardar(Producto $producto): void
    {
        $this->persist($producto);
        $this->flush();
    }

    public function buscarPorId(int $id): ?Producto
    {
        return $this->find($id);
    }

    public function buscarPorNombre(string $nombre): ?Producto
    {
        return $this->findOneBy(['nombre' => $nombre]);
    }

    public function buscarTodos(): array
    {
        return $this->findAll();
    }

    public function existeConNombre(string $nombre): bool
    {
        return $this->buscarPorNombre($nombre) !== null;
    }
}
