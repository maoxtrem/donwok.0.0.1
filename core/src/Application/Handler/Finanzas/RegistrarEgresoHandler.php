<?php

namespace App\Application\Handler\Finanzas;

use App\Domain\Entity\Gasto;
use App\Domain\Entity\MovimientoFinanciero;
use App\Domain\Entity\Prestamo;
use App\Domain\Repository\CategoriaFinancieraRepositoryInterface;
use App\Domain\Repository\CuentaFinancieraRepositoryInterface;
use App\Domain\Repository\GastoRepositoryInterface;
use App\Domain\Repository\MovimientoFinancieroRepositoryInterface;
use App\Domain\Repository\PrestamoRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class RegistrarEgresoHandler
{
    public function __construct(
        private CategoriaFinancieraRepositoryInterface $categoriaRepo,
        private CuentaFinancieraRepositoryInterface $cuentaRepo,
        private MovimientoFinancieroRepositoryInterface $movimientoRepo,
        private GastoRepositoryInterface $gastoRepo,
        private PrestamoRepositoryInterface $prestamoRepo,
        private EntityManagerInterface $em
    ) {}

    public function handle(array $data): array
    {
        $tipoEgreso = $data['tipo_egreso']; // GASTO | INVERSION | PRESTAMO
        $monto = (float)$data['monto'];
        $categoriaId = (int)$data['categoria_id'];
        $cuentaId = (int)$data['cuenta_id'];
        $descripcion = $data['descripcion'] ?? '';

        $categoria = $this->categoriaRepo->buscarPorId($categoriaId);
        $cuenta = $this->cuentaRepo->buscarPorId($cuentaId);

        if (!$categoria || !$cuenta) {
            throw new \Exception("Categoría o Cuenta no válida.");
        }

        // Caso 1: Se presta dinero (SALE EFECTIVO HOY)
        if ($tipoEgreso === 'PRESTAMO') {
            $entidad = $data['entidad'] ?? 'Persona desconocida';
            $prestamo = new Prestamo('OTORGADO', $monto, $entidad, $cuenta, $descripcion);
            $this->prestamoRepo->guardar($prestamo);
            
            // Creamos un Gasto técnico para que el dinero salga del cierre de caja hoy
            $gastoTecnico = new Gasto("Desembolso Préstamo: $entidad", $monto, $categoria, $cuenta, $entidad, $descripcion, false);
            $this->gastoRepo->guardar($gastoTecnico);
            
            $refId = $prestamo->getId();
        } 
        // Caso 2: Gasto o Inversión (SALE EFECTIVO HOY)
        else {
            $gasto = new Gasto($descripcion, $monto, $categoria, $cuenta, $data['proveedor'] ?? null, $descripcion, false);
            $this->gastoRepo->guardar($gasto);
            $refId = $gasto->getId();
        }

        return [
            'status' => 'PENDIENTE_DE_CIERRE',
            'ref_id' => $refId
        ];
    }
}
