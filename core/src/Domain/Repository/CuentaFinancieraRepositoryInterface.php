<?php

namespace App\Domain\Repository;

use App\Domain\Entity\CuentaFinanciera;

interface CuentaFinancieraRepositoryInterface
{
    public function buscarPorId(int $id): ?CuentaFinanciera;
    public function buscarPorTipo(string $tipo): ?CuentaFinanciera;
    public function buscarPorNombre(string $nombre): ?CuentaFinanciera;
    
    /** @return CuentaFinanciera[] */
    public function listarTodas(): array;

    /** @return CuentaFinanciera[] */
    public function listarActivas(): array;

    /** @return CuentaFinanciera[] */
    public function listarParaFacturacion(): array;
    /** @return CuentaFinanciera[] */
    public function listarParaEgresos(): array;
    /** @return CuentaFinanciera[] */
    public function listarParaCuentasPorPagar(): array;

    /** @return array Resumen de saldos: id, nombre, saldo_actual */
    public function obtenerSaldosActuales(): array;
}
