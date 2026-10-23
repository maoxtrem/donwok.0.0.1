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

        if (!is_finite($monto) || $monto <= 0) {
            throw new \Exception('El monto del egreso debe ser mayor que cero.');
        }

        $categoria = $this->categoriaRepo->buscarPorId($categoriaId);
        $cuenta = $this->cuentaRepo->buscarPorId($cuentaId);

        if (!$categoria || !$cuenta || !$cuenta->estaActiva() || !$cuenta->usarEnEgresos()) {
            throw new \Exception("Categoría o Cuenta no válida.");
        }

        $saldoActual = null;
        foreach ($this->cuentaRepo->obtenerSaldosActuales() as $saldo) {
            if ((int)$saldo['id'] === $cuentaId) {
                $saldoActual = (float)$saldo['saldo'];
                break;
            }
        }
        if ($saldoActual === null) {
            throw new \Exception('No se pudo consultar el saldo de la cuenta.');
        }
        if ($monto > $saldoActual + 0.01) {
            throw new \Exception(sprintf(
                'Saldo insuficiente. Disponible: COP %s.',
                number_format(max(0, $saldoActual), 2, ',', '.')
            ));
        }

        // Caso 1: Se presta dinero (SALE EFECTIVO HOY)
        if ($tipoEgreso === 'PRESTAMO') {
            $entidad = $data['entidad'] ?? 'Persona desconocida';
            $prestamo = new Prestamo('OTORGADO', $monto, $entidad, $cuenta, $categoria, $descripcion);
            $this->prestamoRepo->guardar($prestamo);
            
            // Creamos un Gasto técnico para que el dinero salga del cierre de caja hoy
            $gastoTecnico = new Gasto("Desembolso Préstamo: $entidad", $monto, $categoria, $cuenta, $entidad, $descripcion, false);
            $this->gastoRepo->guardar($gastoTecnico);
            $gasto = $gastoTecnico;
            
            $refId = $prestamo->getId();
        } 
        // Caso 2: Gasto o Inversión (SALE EFECTIVO HOY)
        else {
            $gasto = new Gasto($descripcion, $monto, $categoria, $cuenta, $data['proveedor'] ?? null, $descripcion, false);
            $this->gastoRepo->guardar($gasto);
            $refId = $gasto->getId();
        }

        if (!$cuenta->incluirEnArqueo()) {
            $gasto->cerrar();
            $this->em->persist(new MovimientoFinanciero(
                'EGRESO',
                $monto,
                $categoria,
                $cuenta,
                'gasto',
                $gasto->getId(),
                'Egreso registrado directamente desde ' . $cuenta->getNombre()
            ));
            $this->em->flush();

            return [
                'status' => 'REGISTRADO_EN_LIBRO_MAYOR',
                'ref_id' => $refId,
                'gasto_id' => $gasto->getId(),
                'concepto' => $gasto->getConcepto(),
                'monto' => $gasto->getMonto(),
                'cuenta' => $cuenta->getNombre(),
                'categoria' => $categoria->getNombre()
            ];
        }

        return [
            'status' => 'PENDIENTE_DE_CIERRE',
            'ref_id' => $refId
        ];
    }
}
