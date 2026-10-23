<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\PuestoTrabajo;
use App\Domain\Entity\TurnoPuesto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TurnoPuesto> */
class TurnoPuestoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TurnoPuesto::class);
    }

    public function turnoActivo(PuestoTrabajo $puesto): ?TurnoPuesto
    {
        return $this->findOneBy(['puesto' => $puesto, 'cierre' => null], ['inicio' => 'DESC']);
    }
}
