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

        // Determinamos si es un pago recibido (Ingreso) o un pago realizado (Egreso)
        $tipoMovimiento = ($prestamo->getTipo() === 'OTORGADO') ? 'INGRESO' : 'EGRESO';
        $concepto = ($prestamo->getTipo() === 'OTORGADO') ? "Abono recibido de: " : "Pago de deuda a: ";

        // Creamos el registro del pago, pero isCerrado será false por defecto
        $pago = new PagoPrestamo($prestamo, $monto, $cuenta, $concepto . $prestamo->getEntidad());
        $this->em->persist($pago);
        
        $this->em->flush();
    }
}
