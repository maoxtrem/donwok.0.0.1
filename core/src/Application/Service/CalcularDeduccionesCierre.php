<?php

namespace App\Application\Service;

use App\Domain\Entity\DeduccionCaja;

class CalcularDeduccionesCierre
{
    /** @param DeduccionCaja[] $deducciones */
    public function calcular(float $totalVentas, array $deducciones): array
    {
        $resultado = [];

        foreach ($deducciones as $deduccion) {
            $monto = $deduccion->getTipo() === DeduccionCaja::TIPO_PORCENTAJE
                ? $totalVentas * ($deduccion->getValor() / 100)
                : $deduccion->getValor();

            $resultado[] = [
                'id' => $deduccion->getId(),
                'nombre' => $deduccion->getNombre(),
                'tipo' => $deduccion->getTipo(),
                'valor' => $deduccion->getValor(),
                'monto' => round($monto, 2),
            ];
        }

        return $resultado;
    }
}
