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

        // 1. Crear el registro específico según el tipo
        if ($tipoEgreso === 'PRESTAMO') {
            $entidad = $data['entidad'] ?? 'Persona/Entidad desconocida';
            $prestamo = new Prestamo('OTORGADO', $monto, $entidad, $cuenta, $descripcion);
            $this->prestamoRepo->guardar($prestamo);
            $refId = $prestamo->getId();
        } else {
            $gasto = new Gasto($descripcion, $monto, $categoria, $cuenta, $data['proveedor'] ?? null, $descripcion);
            $this->gastoRepo->guardar($gasto);
            $refId = $gasto->getId();
        }

        return [
            'status' => 'PENDIENTE_DE_CIERRE',
            'ref_id' => $refId
        ];
    }
}
