<?php

namespace App\Application\Service;

use App\Domain\Entity\DatosEmpresa;
use App\Domain\Entity\DeduccionCaja;
use App\Domain\Entity\Prestamo;
use Doctrine\ORM\EntityManagerInterface;

class DeduccionesCierreService
{
    public function __construct(private EntityManagerInterface $em) {}

    /** @return DeduccionCaja[] */
    public function obtenerAplicables(DatosEmpresa $empresa): array
    {
        $deducciones = $this->em->getRepository(DeduccionCaja::class)->findBy(
            ['empresa' => $empresa, 'activo' => true],
            ['id' => 'ASC']
        );
        $hoy = new \DateTime('today', new \DateTimeZone('America/Bogota'));
        $resultado = [];

        foreach ($deducciones as $deduccion) {
            if ($deduccion->isCadaCierre()) {
                $resultado[] = $deduccion;
                continue;
            }

            $generadaHoy = $this->em->getRepository(Prestamo::class)->findOneBy([
                'deduccionCaja' => $deduccion,
                'fechaInicio' => $hoy,
            ]);
            if (!$generadaHoy) {
                $resultado[] = $deduccion;
            }
        }

        return $resultado;
    }
}
