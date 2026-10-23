<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\PuestoTrabajo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PuestoTrabajo> */
class PuestoTrabajoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PuestoTrabajo::class);
    }

    /** @return PuestoTrabajo[] */
    public function listarPublicos(): array
    {
        return $this->findBy(['activo' => true], ['fechaCreacion' => 'DESC']);
    }

    /** @return PuestoTrabajo[] */
    public function listarTodos(): array
    {
        return $this->findBy([], ['fechaCreacion' => 'DESC']);
    }

    /** @return PuestoTrabajo[] */
    public function listarPublicosParaDia(string $dia): array
    {
        return array_values(array_filter(
            $this->listarPublicos(),
            static fn(PuestoTrabajo $puesto) => in_array($dia, $puesto->getDiasActivos(), true)
        ));
    }
}
