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

    public function handle(int $prestamoId, float $monto, int $cuentaId): array
    {
        $prestamo = $this->prestamoRepo->buscarPorId($prestamoId);
        $cuenta = $this->cuentaRepo->buscarPorId($cuentaId);
        
        if (!$prestamo) throw new \Exception("Préstamo no encontrado.");
        if (!$cuenta || !$cuenta->estaActiva() || $cuenta->esTemporal()) {
            throw new \Exception("La cuenta seleccionada no está disponible para realizar el pago.");
        }

        $prestamo->registrarAbono($monto);

        // Determinamos si es un pago recibido (Ingreso) o un pago realizado (Egreso)
        $tipoMovimiento = ($prestamo->getTipo() === 'OTORGADO') ? 'INGRESO' : 'EGRESO';
        $concepto = ($prestamo->getTipo() === 'OTORGADO') ? "Abono recibido de: " : "Pago de deuda a: ";

        // Creamos el registro del pago. Los abonos de cartera son ingresos y los
        // abonos a obligaciones son egresos.
        $pago = new PagoPrestamo($prestamo, $monto, $cuenta, $concepto . $prestamo->getEntidad());
        $this->em->persist($pago);

        // Una cuenta fuera de arqueo nunca debe entrar en la cola del arqueo.
        // Se registra inmediatamente en el libro mayor, tanto si es un cobro de
        // cartera como si es un pago de una obligación.
        $registroInmediato = !$cuenta->incluirEnArqueo();
        if ($registroInmediato) {
            $nombreCategoria = $prestamo->getTipo() === 'OTORGADO'
                ? 'Cobro de Préstamo'
                : 'Pago de Obligaciones';
            $categoria = $this->categoriaRepo->buscarPorNombre($nombreCategoria);
            if (!$categoria) {
                throw new \Exception('No existe la categoría financiera ' . $nombreCategoria . '.');
            }
            $this->em->persist(new MovimientoFinanciero(
                $tipoMovimiento,
                $monto,
                $categoria,
                $cuenta,
                'prestamo',
                $prestamo->getId(),
                $concepto . $prestamo->getEntidad()
            ));
            $pago->cerrar();
        }
        
        $this->em->flush();

        return [
            'status' => $registroInmediato ? 'REGISTRADO_EN_LIBRO_MAYOR' : 'PENDIENTE_DE_CIERRE',
            'pago_id' => $pago->getId(),
            'concepto' => $concepto . $prestamo->getEntidad(),
            'monto' => $monto,
            'cuenta' => $cuenta->getNombre(),
            'categoria' => $prestamo->getTipo() === 'OTORGADO' ? 'Cobro de Préstamo' : 'Pago de Obligaciones',
            'tipo_movimiento' => $tipoMovimiento,
            'referencia_label' => 'ID PAGO',
        ];
    }
}
