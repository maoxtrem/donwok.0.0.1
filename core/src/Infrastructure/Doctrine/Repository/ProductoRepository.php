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
        $this->getEntityManager()->persist($producto);
        $this->getEntityManager()->flush();
    }

    public function buscarPorId(int $id): ?Producto
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id = :id')
            ->andWhere('p.fechaEliminacion IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function buscarTodos(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.fechaEliminacion IS NULL')
            ->getQuery()
            ->getResult();
    }


    public function buscarPorNombre(string $nombre): ?Producto
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.nombre = :nombre')
            ->andWhere('p.fechaEliminacion IS NULL')
            ->setParameter('nombre', $nombre)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existeConNombre(string $nombre): bool
    {
        return $this->buscarPorNombre($nombre) !== null;
    }
}
