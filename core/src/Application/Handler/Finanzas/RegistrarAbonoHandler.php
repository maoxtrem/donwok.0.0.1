<?php

namespace App\Application\Handler\Finanzas;

use App\Domain\Entity\PagoPrestamo;
use App\Domain\Entity\MovimientoFinanciero;
use App\Domain\Repository\CuentaFinancieraRepositoryInterface;
use App\Domain\Repository\MovimientoFinancieroRepositoryInterface;
use App\Domain\Repository\PrestamoRepositoryInterface;
use App\Domain\Repository\CategoriaFinancieraRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class RegistrarAbonoHandler
{
    public function __construct(
        private PrestamoRepositoryInterface $prestamoRepo,
        private CuentaFinancieraRepositoryInterface $cuentaRepo,
        private CategoriaFinancieraRepositoryInterface $categoriaRepo,
        private MovimientoFinancieroRepositoryInterface $movimientoRepo,
        private EntityManagerInterface $em
    ) {}

    public function handle(int $prestamoId, float $monto, int $cuentaId): void
    {
        $prestamo = $this->prestamoRepo->buscarPorId($prestamoId);
        $cuenta = $this->cuentaRepo->buscarPorId($cuentaId);
        
        if (!$prestamo) throw new \Exception("Préstamo no encontrado.");
        if (!$cuenta) throw new \Exception("Cuenta no válida.");

        $prestamo->registrarAbono($monto);

        // Creamos el registro del pago, pero isCerrado será false por defecto
        $pago = new PagoPrestamo($prestamo, $monto, $cuenta, "Abono a préstamo");
        $this->em->persist($pago);
        
        $this->em->flush();
    }
}
