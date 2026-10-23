<?php

namespace App\UI\Http\Web\Controller;

use App\Application\Handler\Finanzas\AnularFacturaHandler;
use App\Application\Handler\Finanzas\RealizarCierreCajaHandler;
use App\Application\Handler\Finanzas\RegistrarEgresoHandler;
use App\Application\Handler\Finanzas\RegistrarAbonoHandler;
use App\Application\Handler\Finanzas\RegistrarDeudaHandler;
use App\Domain\Entity\DatosEmpresa;
use App\Domain\Entity\DeduccionCaja;
use App\Application\Service\CalcularDeduccionesCierre;
use App\Application\Service\DeduccionesCierreService;
use App\Domain\Entity\Factura;
use App\Domain\Entity\CuentaFinanciera;
use App\Domain\Entity\CategoriaFinanciera;
use App\Domain\Entity\MovimientoFinanciero;
use App\Domain\Entity\TransferenciaArqueo;
use App\Domain\Repository\FacturaRepositoryInterface;
use App\Domain\Repository\MovimientoFinancieroRepositoryInterface;
use App\Domain\Repository\CategoriaFinancieraRepositoryInterface;
use App\Domain\Repository\CuentaFinancieraRepositoryInterface;
use App\Domain\Repository\GastoRepositoryInterface;
use App\Domain\Repository\PrestamoRepositoryInterface;
use App\Domain\Repository\PagoPrestamoRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Process\Process;

#[Route('/caja')]
class CajaController extends AbstractController
{
    public function __construct(
        private RealizarCierreCajaHandler $cierreHandler,
        private AnularFacturaHandler $anularHandler,
        private RegistrarEgresoHandler $egresoHandler,
        private RegistrarAbonoHandler $abonoHandler,
        private RegistrarDeudaHandler $deudaHandler,
        private GastoRepositoryInterface $gastoRepo,
        private PrestamoRepositoryInterface $prestamoRepo,
        private PagoPrestamoRepositoryInterface $pagoRepo,
        private \Doctrine\ORM\EntityManagerInterface $em,
        private string $printerName
    ) {}

    #[Route('/deudas', name: 'app_caja_deudas_empresa', methods: ['GET'])]
    public function deudas(PrestamoRepositoryInterface $repo): JsonResponse
    {
        $deudas = $repo->buscarDeudasPendientes();
        return new JsonResponse(array_map(fn($p) => [
            'id' => $p->getId(),
            'entidad' => $p->getEntidad(),
            'montoTotal' => $p->getMontoTotal(),
            'saldoPendiente' => $p->getSaldoPendiente(),
            'estado' => $p->getEstado(),
            'categoria' => $p->getCategoriaFinanciera()->getNombre(),
            'observaciones' => $p->getObservaciones(),
            'fecha' => $p->getFechaCreacion()->format('Y-m-d')
        ], $deudas));
    }

    #[Route('/deudas/{id}', name: 'app_caja_deuda_anular', methods: ['DELETE'])]
    public function anularDeuda(int $id, PrestamoRepositoryInterface $repo): JsonResponse
    {
        $deuda = $repo->buscarPorId($id);
        if (!$deuda) {
            return new JsonResponse(['message' => 'Obligación no encontrada.'], 404);
        }
        if ($deuda->getTipo() !== 'RECIBIDO' || $deuda->getEstado() !== 'PENDIENTE') {
            return new JsonResponse(['message' => 'Solo se pueden anular obligaciones pendientes.'], 400);
        }
        if ($deuda->getSaldoPendiente() < $deuda->getMontoTotal()) {
            return new JsonResponse(['message' => 'No se puede anular una obligación que ya tiene abonos registrados.'], 400);
        }

        $categoria = $this->em->getRepository(CategoriaFinanciera::class)
            ->findOneBy(['nombre' => 'Anulación de obligación']);
        if (!$categoria) {
            $categoria = new CategoriaFinanciera(
                'Anulación de obligación',
                'MIXTO',
                'Registro de eliminación lógica de obligaciones'
            );
            $this->em->persist($categoria);
        }

        $deuda->anular();
        $this->em->persist(new MovimientoFinanciero(
            'ANULACION',
            $deuda->getMontoTotal(),
            $categoria,
            $deuda->getCuentaFinanciera(),
            'anulacion_obligacion',
            $deuda->getId(),
            'Obligación anulada lógicamente: ' . ($deuda->getEntidad() ?: 'Sin entidad')
        ));
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Obligación anulada lógicamente y registrada en el libro mayor.',
            'id' => $deuda->getId()
        ]);
    }

    #[Route('/deudas/registrar', name: 'app_caja_deuda_registrar', methods: ['POST'])]
    public function registrarDeuda(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        try {
            $res = $this->deudaHandler->handle($data);
            return new JsonResponse(['message' => 'Obligación registrada con éxito', 'detalle' => $res]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/egresos-pendientes', name: 'app_caja_egresos_pendientes', methods: ['GET'])]
    public function egresosPendientes(): JsonResponse
    {
        $gastos = array_values(array_filter(
            $this->gastoRepo->findPendientesCierre(),
            fn($gasto) => $gasto->getCuentaFinanciera()->incluirEnArqueo()
        ));
        $abonos = array_values(array_filter(
            $this->pagoRepo->findPendientesCierre(),
            fn($abono) => $abono->getCuentaFinanciera()->incluirEnArqueo()
        ));

        $data = [];
        foreach ($gastos as $g) {
            $data[] = [
                'id' => $g->getId(),
                'tipo' => 'GASTO',
                'concepto' => $g->getConcepto(),
                'monto' => $g->getMonto(),
                'categoria' => $g->getCategoriaFinanciera()->getNombre(),
                    'cuenta' => $g->getCuentaFinanciera()->getNombre(),
                    'cuenta_tipo' => $g->getCuentaFinanciera()->getTipo()
            ];
        }
        
        // Solo incluimos abonos que son PAGOS DE DEUDA (RECIBIDO y NO es el desembolso inicial)
        foreach ($abonos as $a) {
            if ($a->getPrestamo()->getTipo() === 'RECIBIDO' && !$a->esDesembolso()) {
                $data[] = [
                    'id' => $a->getId(),
                    'tipo' => 'ABONO_PAGO',
                    'concepto' => 'Pago de deuda: ' . $a->getPrestamo()->getEntidad(),
                    'monto' => $a->getMonto(),
                    'categoria' => 'Pago de Deuda',
                    'cuenta' => $a->getCuentaFinanciera()->getNombre(),
                    'cuenta_tipo' => $a->getCuentaFinanciera()->getTipo()
                ];
            }
        }

        return new JsonResponse($data);
    }

    #[Route('/egresos/{id}', name: 'app_caja_egreso_eliminar', methods: ['DELETE'])]
    public function eliminarEgreso(int $id): JsonResponse
    {
        $gasto = $this->gastoRepo->buscarPorId($id);
        if (!$gasto) return new JsonResponse(['message' => 'No encontrado'], 404);
        if ($gasto->isCerrado()) return new JsonResponse(['message' => 'No se puede eliminar un gasto ya cerrado'], 400);

        $this->gastoRepo->eliminar($gasto);
        return new JsonResponse(['message' => 'Egreso eliminado']);
    }

    #[Route('/prestamos-cola/{id}', name: 'app_caja_prestamo_cola_eliminar', methods: ['DELETE'])]
    public function eliminarPrestamoCola(int $id): JsonResponse
    {
        $prestamo = $this->prestamoRepo->buscarPorId($id);
        if (!$prestamo) return new JsonResponse(['message' => 'No encontrado'], 404);
        
        // Un préstamo puede ser eliminado si no ha tenido abonos y no ha sido cerrado (desembolso)
        // Por simplicidad, permitimos eliminar si el saldo es igual al monto total
        if ($prestamo->getSaldoPendiente() < $prestamo->getMontoTotal()) {
            return new JsonResponse(['message' => 'No se puede eliminar un préstamo que ya tiene abonos registrados'], 400);
        }

        $this->prestamoRepo->eliminar($prestamo);
        return new JsonResponse(['message' => 'Préstamo eliminado de la cola']);
    }

    #[Route('/categorias', name: 'app_caja_categorias', methods: ['GET'])]
    public function categorias(CategoriaFinancieraRepositoryInterface $repo): JsonResponse
    {
        return new JsonResponse(array_map(fn($c) => [
            'id' => $c->getId(),
            'nombre' => $c->getNombre(),
            'tipo' => $c->getTipo()
        ], $repo->listarTodas()));
    }

    #[Route('/cuentas', name: 'app_caja_cuentas', methods: ['GET'])]
    public function cuentas(CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        return new JsonResponse(array_map(fn($c) => [
            'id' => $c->getId(),
            'nombre' => $c->getNombre(),
            'tipo' => $c->getTipo()
        ], $repo->listarActivas()));
    }

    #[Route('/cuentas/gestion', name: 'app_caja_cuentas_gestion', methods: ['GET'])]
    public function cuentasGestion(CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        $saldos = [];
        foreach ($repo->obtenerSaldosActuales() as $saldo) {
            $saldos[(string)$saldo['id']] = $saldo['saldo'];
        }

        return new JsonResponse(array_map(fn($c) => [
            'id' => $c->getId(),
            'nombre' => $c->getNombre(),
            'tipo' => $c->getTipo(),
            'activa' => $c->estaActiva(),
            'usar_en_facturacion' => $c->usarEnFacturacion(),
            'incluir_en_arqueo' => $c->incluirEnArqueo(),
            'mostrar_en_balance' => $c->mostrarEnBalance(),
            'usar_en_egresos' => $c->usarEnEgresos(),
            'usar_en_cuentas_por_pagar' => $c->usarEnCuentasPorPagar(),
            'es_temporal' => $c->esTemporal(),
            'editable' => $c->esEditable(),
            'saldo' => $saldos[(string)$c->getId()] ?? 0,
            'saldo_base' => $c->getSaldoBase()
        ], $repo->listarTodas()));
    }

    #[Route('/cuentas/facturacion', name: 'app_caja_cuentas_facturacion', methods: ['GET'])]
    public function cuentasFacturacion(CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        return new JsonResponse(array_map(fn($c) => [
            'id' => $c->getId(),
            'nombre' => $c->getNombre(),
            'tipo' => $c->getTipo()
        ], $repo->listarParaFacturacion()));
    }

    #[Route('/cuentas/egresos', name: 'app_caja_cuentas_egresos', methods: ['GET'])]
    public function cuentasEgresos(CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        return new JsonResponse(array_map(fn($c) => [
            'id' => $c->getId(),
            'nombre' => $c->getNombre(),
            'tipo' => $c->getTipo()
        ], $repo->listarParaEgresos()));
    }

    #[Route('/cuentas/cuentas-por-pagar', name: 'app_caja_cuentas_por_pagar', methods: ['GET'])]
    public function cuentasPorPagar(CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        return new JsonResponse(array_map(fn($c) => [
            'id' => $c->getId(),
            'nombre' => $c->getNombre(),
            'tipo' => $c->getTipo()
        ], $repo->listarParaCuentasPorPagar()));
    }

    #[Route('/cuentas/pago-obligaciones', name: 'app_caja_cuentas_pago_obligaciones', methods: ['GET'])]
    public function cuentasPagoObligaciones(CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        return new JsonResponse(array_map(fn($c) => [
            'id' => $c->getId(),
            'nombre' => $c->getNombre(),
            'tipo' => $c->getTipo()
        ], array_values(array_filter(
            $repo->listarActivas(),
            fn($c) => !$c->esTemporal()
        ))));
    }

    #[Route('/cuentas/{id}/facturacion', name: 'app_caja_cuenta_facturacion', methods: ['PATCH'])]
    public function cambiarUsoFacturacion(int $id, CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        $cuenta = $repo->buscarPorId($id);
        if (!$cuenta) return new JsonResponse(['message' => 'Cuenta no encontrada.'], 404);
        if ($cuenta->esTemporal()) return new JsonResponse(['message' => 'Las cuentas temporales no se pueden editar.'], 400);
        if (!$cuenta->estaActiva()) return new JsonResponse(['message' => 'Primero habilita la cuenta.'], 400);

        if (!$cuenta->usarEnFacturacion()) {
            $seleccionadas = count($repo->listarParaFacturacion());
            if ($seleccionadas >= 2) {
                return new JsonResponse(['message' => 'Solo puedes seleccionar hasta 2 cuentas para facturación.'], 400);
            }
        }

        if ($cuenta->usarEnFacturacion()) {
            $cuenta->desactivarParaFacturacion();
        } else {
            $cuenta->activarParaFacturacion();
        }
        $this->em->flush();

        return new JsonResponse(['message' => $cuenta->usarEnFacturacion() ? 'Cuenta disponible en facturación.' : 'Cuenta retirada de facturación.']);
    }

    #[Route('/cuentas/{id}/arqueo', name: 'app_caja_cuenta_arqueo', methods: ['PATCH'])]
    public function cambiarUsoArqueo(int $id, CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        $cuenta = $repo->buscarPorId($id);
        if (!$cuenta) return new JsonResponse(['message' => 'Cuenta no encontrada.'], 404);
        if ($cuenta->esTemporal()) return new JsonResponse(['message' => 'Las cuentas temporales no se pueden editar.'], 400);

        if ($cuenta->incluirEnArqueo()) {
            $cuenta->desactivarParaArqueo();
        } else {
            $cuenta->activarParaArqueo();
        }
        $this->em->flush();

        return new JsonResponse(['message' => $cuenta->incluirEnArqueo() ? 'Cuenta incluida en el arqueo.' : 'Cuenta excluida del arqueo.']);
    }

    #[Route('/cuentas/{id}/balance', name: 'app_caja_cuenta_balance', methods: ['PATCH'])]
    public function cambiarVisibilidadBalance(int $id, CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        $cuenta = $repo->buscarPorId($id);
        if (!$cuenta) return new JsonResponse(['message' => 'Cuenta no encontrada.'], 404);
        if ($cuenta->esTemporal()) return new JsonResponse(['message' => 'Las cuentas temporales no se pueden editar.'], 400);

        if ($cuenta->mostrarEnBalance()) {
            $cuenta->desactivarParaBalance();
        } else {
            $cuenta->activarParaBalance();
        }
        $this->em->flush();

        return new JsonResponse(['message' => $cuenta->mostrarEnBalance() ? 'Cuenta visible en el balance.' : 'Cuenta oculta del balance.']);
    }

    #[Route('/cuentas/{id}/egresos', name: 'app_caja_cuenta_egresos', methods: ['PATCH'])]
    public function cambiarUsoEgresos(int $id, CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        $cuenta = $repo->buscarPorId($id);
        if (!$cuenta) return new JsonResponse(['message' => 'Cuenta no encontrada.'], 404);
        if ($cuenta->esTemporal()) return new JsonResponse(['message' => 'Las cuentas temporales no se pueden editar.'], 400);

        if ($cuenta->usarEnEgresos()) {
            $cuenta->desactivarParaEgresos();
        } else {
            $cuenta->activarParaEgresos();
        }
        $this->em->flush();

        return new JsonResponse(['message' => $cuenta->usarEnEgresos() ? 'Cuenta disponible para egresos.' : 'Cuenta retirada de egresos.']);
    }

    #[Route('/cuentas/{id}/cuentas-por-pagar', name: 'app_caja_cuenta_cuentas_por_pagar', methods: ['PATCH'])]
    public function cambiarUsoCuentasPorPagar(int $id, CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        $cuenta = $repo->buscarPorId($id);
        if (!$cuenta) return new JsonResponse(['message' => 'Cuenta no encontrada.'], 404);
        if ($cuenta->esTemporal()) return new JsonResponse(['message' => 'Las cuentas temporales no se pueden editar.'], 400);

        if ($cuenta->usarEnCuentasPorPagar()) {
            $cuenta->desactivarParaCuentasPorPagar();
        } else {
            $cuenta->activarParaCuentasPorPagar();
        }
        $this->em->flush();

        return new JsonResponse(['message' => $cuenta->usarEnCuentasPorPagar() ? 'Cuenta disponible para cuentas por pagar.' : 'Cuenta retirada de cuentas por pagar.']);
    }

    #[Route('/cuentas', name: 'app_caja_cuenta_crear', methods: ['POST'])]
    public function crearCuenta(Request $request, CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $nombre = trim((string)($data['nombre'] ?? ''));
        $tipo = strtoupper(trim((string)($data['tipo'] ?? '')));
        $saldoInicial = (float)($data['saldo_inicial'] ?? 0);
        $saldoBase = (float)($data['saldo_base'] ?? 0);

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            return new JsonResponse(['message' => 'El nombre de la cuenta es obligatorio.'], 400);
        }
        if (!in_array($tipo, ['CAJA', 'BANCO', 'BILLETERA'], true)) {
            return new JsonResponse(['message' => 'El tipo de cuenta no es válido.'], 400);
        }
        if ($saldoInicial < 0) {
            return new JsonResponse(['message' => 'El saldo inicial no puede ser negativo.'], 400);
        }
        if ($saldoBase < 0) {
            return new JsonResponse(['message' => 'El saldo base no puede ser negativo.'], 400);
        }
        if ($repo->buscarPorNombre($nombre)) {
            return new JsonResponse(['message' => 'Ya existe una cuenta con ese nombre.'], 409);
        }

        $cuenta = new CuentaFinanciera($nombre, $tipo, $saldoInicial, $saldoBase);
        if (($data['editable'] ?? false) === true) {
            $cuenta->activarEdicion();
        }
        $this->em->persist($cuenta);
        $this->em->flush();

        return new JsonResponse(['message' => 'Cuenta creada correctamente.', 'id' => $cuenta->getId()], 201);
    }

    #[Route('/cuentas/{id}/saldo-base', name: 'app_caja_cuenta_saldo_base', methods: ['PATCH'])]
    public function cambiarSaldoBase(int $id, Request $request, CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        $cuenta = $repo->buscarPorId($id);
        if (!$cuenta) {
            return new JsonResponse(['message' => 'Cuenta no encontrada.'], 404);
        }
        if ($cuenta->esTemporal()) {
            return new JsonResponse(['message' => 'Las cuentas temporales no tienen saldo base editable.'], 400);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $saldoBase = (float)($data['saldo_base'] ?? -1);
        if ($saldoBase < 0 || !is_finite($saldoBase)) {
            return new JsonResponse(['message' => 'El saldo base debe ser un número no negativo.'], 400);
        }

        $cuenta->setSaldoBase($saldoBase);
        $this->em->flush();

        return new JsonResponse(['message' => 'Saldo base actualizado.', 'saldo_base' => $cuenta->getSaldoBase()]);
    }

    #[Route('/cuentas/{id}/editable', name: 'app_caja_cuenta_editable', methods: ['PATCH'])]
    public function cambiarEditable(int $id, CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        $cuenta = $repo->buscarPorId($id);
        if (!$cuenta) return new JsonResponse(['message' => 'Cuenta no encontrada.'], 404);
        if ($cuenta->esTemporal()) return new JsonResponse(['message' => 'Las cuentas temporales no se pueden editar.'], 400);

        $cuenta->esEditable() ? $cuenta->desactivarEdicion() : $cuenta->activarEdicion();
        $this->em->flush();
        return new JsonResponse(['message' => $cuenta->esEditable() ? 'Cuenta editable.' : 'Edición de cuenta bloqueada.']);
    }

    #[Route('/cuentas/{id}/editar', name: 'app_caja_cuenta_editar', methods: ['PATCH'])]
    public function editarCuenta(int $id, Request $request, CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        $cuenta = $repo->buscarPorId($id);
        if (!$cuenta) return new JsonResponse(['message' => 'Cuenta no encontrada.'], 404);
        if ($cuenta->esTemporal()) return new JsonResponse(['message' => 'Las cuentas temporales no se pueden editar.'], 400);
        if (!$cuenta->esEditable()) return new JsonResponse(['message' => 'Activa la bandera editable para modificar esta cuenta.'], 400);

        $data = json_decode($request->getContent(), true) ?? [];
        $nombre = trim((string)($data['nombre'] ?? $cuenta->getNombre()));
        $operacion = strtoupper(trim((string)($data['operacion'] ?? '')));
        $montoAjuste = array_key_exists('monto', $data) ? (float)$data['monto'] : 0.0;
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            return new JsonResponse(['message' => 'El nombre de la cuenta no es válido.'], 400);
        }
        if (!in_array($operacion, ['', 'INGRESO', 'EGRESO'], true)) {
            return new JsonResponse(['message' => 'La operación debe ser INGRESO o EGRESO.'], 400);
        }
        if (!is_finite($montoAjuste) || $montoAjuste < 0) {
            return new JsonResponse(['message' => 'El monto debe ser un número no negativo.'], 400);
        }
        if ($montoAjuste > 0 && $operacion === '') {
            return new JsonResponse(['message' => 'Selecciona si el monto se agrega o se retira.'], 400);
        }
        $otra = $repo->buscarPorNombre($nombre);
        if ($otra && $otra->getId() !== $cuenta->getId()) {
            return new JsonResponse(['message' => 'Ya existe una cuenta con ese nombre.'], 409);
        }

        $saldoActual = null;
        foreach ($repo->obtenerSaldosActuales() as $saldo) {
            if ((int)$saldo['id'] === $id) {
                $saldoActual = (float)$saldo['saldo'];
                break;
            }
        }
        if ($saldoActual === null) return new JsonResponse(['message' => 'No se pudo consultar el saldo actual.'], 400);

        $cuenta->setNombre($nombre);
        $montoAjuste = round($montoAjuste, 2);
        $saldoNuevo = $saldoActual;
        if ($montoAjuste > 0.0) {
            $categoria = $this->em->getRepository(CategoriaFinanciera::class)->findOneBy(['nombre' => 'Ajuste de saldo']);
            if (!$categoria) {
                $categoria = new CategoriaFinanciera('Ajuste de saldo', 'MIXTO', 'Ajustes manuales auditables de cuentas');
                $this->em->persist($categoria);
            }
            $saldoNuevo = round($saldoActual + ($operacion === 'INGRESO' ? $montoAjuste : -$montoAjuste), 2);
            if ($saldoNuevo < 0) {
                return new JsonResponse(['message' => 'El egreso no puede superar el saldo actual de la cuenta.'], 400);
            }
            $this->em->persist(new MovimientoFinanciero(
                $operacion,
                $montoAjuste,
                $categoria,
                $cuenta,
                'ajuste_cuenta',
                $cuenta->getId(),
                ($operacion === 'INGRESO' ? 'Ingreso' : 'Egreso') . ' manual en ' . $cuenta->getNombre()
            ));
        }
        $this->em->flush();

        $impresion = '';
        if ($montoAjuste > 0.0) {
            try {
                $this->imprimirAjusteCuenta($cuenta, $operacion, $montoAjuste, $saldoActual, $saldoNuevo);
                $impresion = ' Comprobante enviado a impresión.';
            } catch (\Throwable $e) {
                error_log('Error imprimiendo ajuste de cuenta: ' . $e->getMessage());
                $impresion = ' No se pudo imprimir el comprobante: ' . $e->getMessage();
            }
        }

        return new JsonResponse([
            'message' => 'Cuenta actualizada y movimiento registrado.' . $impresion,
            'saldo_anterior' => $saldoActual,
            'saldo' => $saldoNuevo,
            'operacion' => $operacion ?: null,
            'monto' => $montoAjuste
        ]);
    }

    private function imprimirAjusteCuenta(CuentaFinanciera $cuenta, string $operacion, float $monto, float $saldoAnterior, float $saldoNuevo): void
    {
        $ticket = "\x1b@\x1ba\x01\x1bE\x01DON WOK\n";
        $ticket .= "\x1bE\x00AJUSTE DE CAJA\n";
        $ticket .= "FECHA: " . (new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota')))->format('Y-m-d H:i:s') . "\n";
        $ticket .= str_repeat('-', 32) . "\n";
        $ticket .= 'CUENTA: ' . $cuenta->getNombre() . "\n";
        $ticket .= 'OPERACION: ' . ($operacion === 'INGRESO' ? 'INGRESO / AGREGAR' : 'EGRESO / RETIRAR') . "\n";
        $ticket .= 'MONTO: COP ' . number_format($monto, 0, ',', '.') . "\n";
        $ticket .= 'SALDO ANTERIOR: COP ' . number_format($saldoAnterior, 0, ',', '.') . "\n";
        $ticket .= 'SALDO NUEVO: COP ' . number_format($saldoNuevo, 0, ',', '.') . "\n";
        $ticket .= str_repeat('-', 32) . "\n";
        $ticket .= "REGISTRADO EN LIBRO MAYOR\n";
        $ticket .= "\x1bd\x06\x1d\x56\x00";

        $process = new Process(['lp', '-o', 'raw', '-d', $this->printerName], null, null, $ticket);
        $process->setTimeout(15);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'La impresora no aceptó el ajuste.');
        }
    }

    #[Route('/cuentas/{id}/estado', name: 'app_caja_cuenta_estado', methods: ['PATCH'])]
    public function cambiarEstadoCuenta(int $id, CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        $cuenta = $repo->buscarPorId($id);
        if (!$cuenta) {
            return new JsonResponse(['message' => 'Cuenta no encontrada.'], 404);
        }
        if ($cuenta->esTemporal()) {
            return new JsonResponse(['message' => 'Las cuentas temporales no se pueden editar.'], 400);
        }

        if ($cuenta->estaActiva()) {
            $cuenta->deshabilitar();
        } else {
            $cuenta->habilitar();
        }
        $this->em->flush();

        return new JsonResponse(['message' => $cuenta->estaActiva() ? 'Cuenta habilitada.' : 'Cuenta deshabilitada.']);
    }

    #[Route('/cierres/{identificador}', name: 'app_caja_cierre_detalle', methods: ['GET'])]
    public function detalleCierre(string $identificador): JsonResponse
    {
        $auditorias = $this->em->getRepository(TransferenciaArqueo::class)->findBy(
            ['identificadorCierre' => $identificador],
            ['id' => 'ASC']
        );

        if ($auditorias === []) {
            return new JsonResponse(['message' => 'No se encontró el cierre solicitado.'], 404);
        }

        $primera = $auditorias[0];
        return new JsonResponse([
            'identificador_cierre' => $identificador,
            'fecha_cierre' => $primera->getFechaArqueo()->format('Y-m-d H:i:s'),
            'auditado_por' => $primera->getAuditadoPor(),
            'total_cuentas' => count($auditorias),
            'total_auditado' => round(array_sum(array_map(fn(TransferenciaArqueo $a) => $a->getMonto(), $auditorias)), 2),
            'cuentas' => array_map(fn(TransferenciaArqueo $a) => [
                'cuenta_origen' => $a->getCuentaOrigenNombre(),
                'cuenta_temporal' => $a->getCuentaTemporalNombre(),
                'valor_contado' => $a->getValorContado(),
                'monto' => $a->getMonto(),
                'estado' => $a->getEstado(),
                'destino' => $a->getCuentaDestinoNombre(),
                'movido_por' => $a->getMovidoPor(),
                'fecha_traslado' => $a->getFechaTraslado()?->format('Y-m-d H:i:s'),
            ], $auditorias),
        ]);
    }

    #[Route('/transferencias', name: 'app_caja_transferencia', methods: ['POST'])]
    public function transferir(Request $request, CuentaFinancieraRepositoryInterface $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $origenId = (int)($data['origen_id'] ?? 0);
        $destinoId = (int)($data['destino_id'] ?? 0);
        $monto = (float)($data['monto'] ?? 0);

        if ($origenId <= 0 || $destinoId <= 0 || $origenId === $destinoId || $monto < 0) {
            return new JsonResponse(['message' => 'Indica dos cuentas diferentes y un monto válido.'], 400);
        }

        $origen = $repo->buscarPorId($origenId);
        $destino = $repo->buscarPorId($destinoId);
        if (!$origen || !$destino) {
            return new JsonResponse(['message' => 'Una de las cuentas no existe.'], 404);
        }
        if (!$origen->esTemporal() && $origen->incluirEnArqueo()) {
            return new JsonResponse(['message' => 'Las cuentas incluidas en arqueo solo pueden mover dinero mediante el cierre de caja.'], 400);
        }
        if ($origen->esTemporal() && $destino->esTemporal()) {
            return new JsonResponse(['message' => 'Una cuenta temporal solo puede trasladarse a una cuenta no temporal.'], 400);
        }
        if (!$destino->esTemporal() && $destino->incluirEnArqueo()) {
            return new JsonResponse(['message' => 'Las cuentas seleccionadas para arqueo no pueden recibir transferencias manuales.'], 400);
        }
        if (!$origen->esTemporal() && $destino->esTemporal()) {
            return new JsonResponse(['message' => 'Las cuentas temporales solo reciben el traslado automático de un arqueo.'], 400);
        }
        if ($origen->esTemporal() && !$destino->esTemporal()) {
            $auditoria = $this->em->getRepository(TransferenciaArqueo::class)->findOneBy([
                'cuentaTemporalId' => $origenId,
                'estado' => 'PENDIENTE'
            ]);
            if (!$auditoria) {
                return new JsonResponse(['message' => 'No existe una auditoría pendiente para esta cuenta temporal.'], 400);
            }
        }
        if (!$origen->estaActiva() || !$destino->estaActiva()) {
            return new JsonResponse(['message' => 'Solo puedes transferir entre cuentas activas.'], 400);
        }
        if ($monto == 0.0 && !$origen->esTemporal()) {
            return new JsonResponse(['message' => 'El monto debe ser mayor que cero.'], 400);
        }

        $saldos = [];
        foreach ($repo->obtenerSaldosActuales() as $saldo) {
            $saldos[(string)$saldo['id']] = [
                'saldo' => (float)$saldo['saldo'],
                'saldo_base' => (float)$saldo['saldo_base'],
                'es_temporal' => (bool)$saldo['es_temporal'],
            ];
        }
        $saldoOrigen = $saldos[(string)$origenId]['saldo'] ?? 0.0;
        $saldoBaseOrigen = $saldos[(string)$origenId]['saldo_base'] ?? 0.0;
        $esTemporalOrigen = $saldos[(string)$origenId]['es_temporal'] ?? $origen->esTemporal();
        $disponibleOrigen = $esTemporalOrigen ? $saldoOrigen : max(0, $saldoOrigen - $saldoBaseOrigen);
        if ($monto > $disponibleOrigen) {
            return new JsonResponse(['message' => 'La cuenta de origen no tiene saldo disponible suficiente, respetando su saldo base.'], 400);
        }
        if ($origen->esTemporal() && abs($saldoOrigen - $monto) > 0.01) {
            return new JsonResponse(['message' => 'La cuenta temporal debe trasladarse por el total auditado.'], 400);
        }

        $categoria = $this->em->getRepository(CategoriaFinanciera::class)->findOneBy(['nombre' => 'Transferencia entre cuentas']);
        if (!$categoria) {
            $categoria = new CategoriaFinanciera('Transferencia entre cuentas', 'MIXTO', 'Movimientos internos entre cuentas financieras');
            $this->em->persist($categoria);
        }

        if (!$origen->esTemporal()) {
            $this->em->persist(new MovimientoFinanciero(
                'EGRESO', $monto, $categoria, $origen, 'transferencia', null,
                'Transferencia a ' . $destino->getNombre()
            ));
        }
        $this->em->persist(new MovimientoFinanciero(
            'INGRESO', $monto, $categoria, $destino, 'transferencia', null,
            'Transferencia desde ' . $origen->getNombre()
        ));
        if ($origen->esTemporal()) {
            $auditoria->marcarComoTrasladada(
                $destino->getId(),
                $destino->getNombre(),
                $this->getUser()?->getUserIdentifier() ?? 'sistema'
            );
            $this->em->persist($auditoria);
            foreach ($this->em->getRepository(MovimientoFinanciero::class)->findBy(['cuentaFinanciera' => $origen]) as $movimientoTemporal) {
                $this->em->remove($movimientoTemporal);
            }
            $this->em->remove($origen);
        }
        $this->em->flush();

        return new JsonResponse([
            'message' => $origen->esTemporal()
                ? 'Cuenta temporal trasladada y eliminada correctamente.'
                : 'Transferencia realizada correctamente.'
        ]);
    }

    #[Route('/egresos', name: 'app_caja_egresos_registrar', methods: ['POST'])]
    public function registrarEgreso(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        try {
            $res = $this->egresoHandler->handle($data);
            $mensaje = ($res['status'] ?? '') === 'REGISTRADO_EN_LIBRO_MAYOR'
                ? 'Egreso registrado directamente en el libro mayor.'
                : 'Egreso registrado con éxito; quedará pendiente del cierre.';
            if (($res['status'] ?? '') === 'REGISTRADO_EN_LIBRO_MAYOR') {
                try {
                    $this->imprimirEgresoDirecto($res);
                    $mensaje .= ' Comprobante enviado a impresión.';
                } catch (\Throwable $e) {
                    error_log('Error imprimiendo egreso directo: ' . $e->getMessage());
                    $mensaje .= ' No se pudo imprimir el comprobante: ' . $e->getMessage();
                }
            }
            return new JsonResponse(['message' => $mensaje, 'detalle' => $res]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    private function imprimirAbonoPrestamo(array $abono): void
    {
        $esIngreso = ($abono['tipo_movimiento'] ?? 'EGRESO') === 'INGRESO';
        $ticket = "\x1b@\x1ba\x01\x1bE\x01DON WOK\n";
        $ticket .= "\x1bE\x00" . ($esIngreso ? "COMPROBANTE DE PAGO\n" : "PAGO DE OBLIGACION\n");
        $ticket .= "FECHA: " . (new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota')))->format('Y-m-d H:i:s') . "\n";
        $ticket .= str_repeat('-', 32) . "\n";
        $ticket .= ($abono['referencia_label'] ?? 'ID PAGO') . ': ' . ($abono['pago_id'] ?? '-') . "\n";
        $ticket .= 'CONCEPTO: ' . ($abono['concepto'] ?? '-') . "\n";
        $ticket .= 'CATEGORIA: ' . ($abono['categoria'] ?? '-') . "\n";
        $ticket .= 'CUENTA: ' . ($abono['cuenta'] ?? '-') . "\n";
        $ticket .= 'MONTO: COP ' . number_format((float)($abono['monto'] ?? 0), 0, ',', '.') . "\n";
        $ticket .= str_repeat('-', 32) . "\n";
        $ticket .= ($abono['status'] ?? '') === 'REGISTRADO_EN_LIBRO_MAYOR'
            ? "REGISTRADO EN LIBRO MAYOR\n"
            : "PENDIENTE DE CIERRE DE CAJA\n";
        $ticket .= "\x1bd\x06\x1d\x56\x00";

        $process = new Process(['lp', '-o', 'raw', '-d', $this->printerName], null, null, $ticket);
        $process->setTimeout(15);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'La impresora no aceptó el egreso.');
        }
    }

    private function imprimirEgresoDirecto(array $egreso): void
    {
        $ticket = "\x1b@\x1ba\x01\x1bE\x01DON WOK\n";
        $ticket .= "\x1bE\x00EGRESO DIRECTO\n";
        $ticket .= "FECHA: " . (new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota')))->format('Y-m-d H:i:s') . "\n";
        $ticket .= str_repeat('-', 32) . "\n";
        $ticket .= ($egreso['referencia_label'] ?? 'ID GASTO') . ': ' . ($egreso['gasto_id'] ?? $egreso['pago_id'] ?? '-') . "\n";
        $ticket .= 'CONCEPTO: ' . ($egreso['concepto'] ?? '-') . "\n";
        $ticket .= 'CATEGORIA: ' . ($egreso['categoria'] ?? '-') . "\n";
        $ticket .= 'CUENTA: ' . ($egreso['cuenta'] ?? '-') . "\n";
        $ticket .= 'MONTO: COP ' . number_format((float)($egreso['monto'] ?? 0), 0, ',', '.') . "\n";
        $ticket .= str_repeat('-', 32) . "\nREGISTRADO EN LIBRO MAYOR\n\x1bd\x06\x1d\x56\x00";

        $process = new Process(['lp', '-o', 'raw', '-d', $this->printerName], null, null, $ticket);
        $process->setTimeout(15);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'La impresora no aceptó el egreso.');
        }
    }

    #[Route('/prestamos', name: 'app_caja_prestamos_activos', methods: ['GET'])]
    public function prestamos(PrestamoRepositoryInterface $repo): JsonResponse
    {
        $prestamos = $repo->buscarActivos();
        return new JsonResponse(array_map(fn($p) => [
            'id' => $p->getId(),
            'entidad' => $p->getEntidad(),
            'montoTotal' => $p->getMontoTotal(),
            'saldoPendiente' => $p->getSaldoPendiente(),
            'estado' => $p->getEstado()
        ], $prestamos));
    }

    #[Route('/prestamos/{id}/abono', name: 'app_caja_prestamo_abono', methods: ['POST'])]
    public function registrarAbono(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        try {
            $resultado = $this->abonoHandler->handle($id, (float)$data['monto'], (int)$data['cuenta_id']);
            $mensaje = $resultado['status'] === 'REGISTRADO_EN_LIBRO_MAYOR'
                ? 'Pago registrado directamente en el libro mayor.'
                : 'Pago registrado; quedará pendiente del cierre.';
            try {
                $this->imprimirAbonoPrestamo($resultado);
                $mensaje .= ' Comprobante enviado a impresión.';
            } catch (\Throwable $e) {
                error_log('Error imprimiendo pago de préstamo: ' . $e->getMessage());
                $mensaje .= ' No se pudo imprimir el comprobante: ' . $e->getMessage();
            }
            return new JsonResponse(['message' => $mensaje, 'detalle' => $resultado]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/facturas-emitidas', name: 'app_caja_facturas_emitidas', methods: ['GET'])]
    public function facturasEmitidas(FacturaRepositoryInterface $repo): JsonResponse
    {
        $facturas = $repo->findPendientesCierre();
        $abonos = array_values(array_filter(
            $this->pagoRepo->findPendientesCierre(),
            fn($abono) => $abono->getCuentaFinanciera()->incluirEnArqueo()
        ));

        $data = [
            'facturas' => array_map(fn($f) => $f->toArray(), $facturas),
            'abonos' => []
        ];

        foreach ($abonos as $a) {
            $esCartera = ($a->getPrestamo()->getTipo() === 'OTORGADO');
            $esCreditoInicial = ($a->getPrestamo()->getTipo() === 'RECIBIDO' && $a->esDesembolso());

            // Si es un cobro a cliente O la entrada inicial de un crédito bancario (Ambos son INGRESOS)
            if ($esCartera || $esCreditoInicial) {
                $data['abonos'][] = [
                    'id' => $a->getId(),
                    'entidad' => $a->getPrestamo()->getEntidad(),
                    'monto' => $a->getMonto(),
                    'cuenta' => $a->getCuentaFinanciera()->getNombre(),
                    'cuenta_tipo' => $a->getCuentaFinanciera()->getTipo(),
                    'fecha' => $a->getFechaCreacion()->format('Y-m-d H:i:s'),
                    'tipo_label' => $esCreditoInicial ? 'CRÉDITO RECIBIDO' : 'COBRO CARTERA'
                ];
            }
        }

        return new JsonResponse($data);
    }

    #[Route('/facturas/{id}/anular', name: 'app_caja_factura_anular', methods: ['POST'])]
    public function anular(int $id): JsonResponse
    {
        try {
            $this->anularHandler->handle($id);
            return new JsonResponse(['message' => 'Factura anulada con éxito']);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/cerrar', name: 'app_caja_cerrar', methods: ['POST'])]
    public function cerrarCaja(Request $request): JsonResponse
    {
        try {
            $resultado = $this->cierreHandler->handle(
                $this->obtenerSaldosContados($request),
                $this->getUser()?->getUserIdentifier() ?? 'sistema'
            );
            return new JsonResponse([
                'message' => 'Cierre de caja realizado con éxito',
                'detalle' => $resultado
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/deducciones-cierre', name: 'app_caja_deducciones_cierre', methods: ['GET'])]
    public function deduccionesCierre(DeduccionesCierreService $deduccionesCierreService): JsonResponse
    {
        $empresa = $this->em->getRepository(DatosEmpresa::class)->findOneBy([]);
        if (!$empresa) {
            return new JsonResponse([]);
        }

        return new JsonResponse(array_map(
            fn(DeduccionCaja $deduccion) => $deduccion->toArray(),
            array_values(array_filter(
                $deduccionesCierreService->obtenerAplicables($empresa),
                fn(DeduccionCaja $deduccion) => $deduccion->isMostrarEnCierre()
            ))
        ));
    }

    #[Route('/imprimir-cierre', name: 'app_caja_imprimir_cierre', methods: ['POST'])]
    public function imprimirCierre(Request $request, FacturaRepositoryInterface $repo, CuentaFinancieraRepositoryInterface $cuentaRepo, CalcularDeduccionesCierre $deduccionesCalculator, DeduccionesCierreService $deduccionesCierreService): JsonResponse
    {
        try {
            $saldosContados = $this->obtenerSaldosContados($request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }

        $facturas = $repo->findPendientesCierre();
        $abonos = array_values(array_filter(
            $this->pagoRepo->findPendientesCierre(),
            fn($abono) => $abono->getCuentaFinanciera()->incluirEnArqueo()
        ));
        $gastos = array_values(array_filter(
            $this->gastoRepo->findPendientesCierre(),
            fn($gasto) => $gasto->getCuentaFinanciera()->incluirEnArqueo()
        ));

        $ingresosAbonos = [];
        foreach ($abonos as $abono) {
            $esCartera = $abono->getPrestamo()->getTipo() === 'OTORGADO';
            $esCreditoInicial = $abono->getPrestamo()->getTipo() === 'RECIBIDO' && $abono->esDesembolso();
            if ($esCartera || $esCreditoInicial) {
                $ingresosAbonos[] = $abono;
            }
        }

        $egresos = $gastos;
        foreach ($abonos as $abono) {
            if ($abono->getPrestamo()->getTipo() === 'RECIBIDO' && !$abono->esDesembolso()) {
                $egresos[] = $abono;
            }
        }

        $totalFacturas = array_reduce($facturas, fn(float $total, Factura $factura) => $total + $factura->getTotal(), 0.0);
        $totalAbonos = array_reduce($ingresosAbonos, fn(float $total, $abono) => $total + $abono->getMonto(), 0.0);
        $totalEgresos = array_reduce($egresos, fn(float $total, $egreso) => $total + $egreso->getMonto(), 0.0);
        $totalIngresos = $totalFacturas + $totalAbonos;

        $empresa = $this->em->getRepository(DatosEmpresa::class)->findOneBy([]);
        $deducciones = $empresa ? $deduccionesCierreService->obtenerAplicables($empresa) : [];
        $deduccionesVisibles = array_values(array_filter(
            $deducciones,
            fn(DeduccionCaja $deduccion) => $deduccion->isMostrarEnCierre()
        ));
        $detalleDeducciones = $deduccionesCalculator->calcular($totalFacturas, $deduccionesVisibles);
        $totalDeducciones = array_sum(array_column($detalleDeducciones, 'monto'));
        $saldosEsperados = [];
        foreach ($cuentaRepo->obtenerSaldosActuales() as $cuenta) {
            $saldosEsperados[(string)$cuenta['id']] = [
                'nombre' => $cuenta['nombre'],
                'saldo' => (float)$cuenta['saldo'],
                'saldo_retenido' => (float)$cuenta['saldo'],
                'tipo' => $cuenta['tipo'],
                'incluir_en_arqueo' => (bool)$cuenta['incluir_en_arqueo'],
                'ventas' => 0.0,
                'otros_ingresos' => 0.0,
                'egresos' => 0.0,
            ];
        }
        $sumarMovimientoEsperado = static function (?int $cuentaId, float $monto, int $factor) use (&$saldosEsperados): void {
            if (!$cuentaId || !isset($saldosEsperados[(string)$cuentaId])) {
                return;
            }
            $saldosEsperados[(string)$cuentaId]['saldo'] += $factor * $monto;
        };
        foreach ($facturas as $factura) {
            $pagos = $factura->getPagos();
            if ($pagos) {
                foreach ($pagos as $pago) {
                    $sumarMovimientoEsperado((int)($pago['cuenta_id'] ?? 0), (float)($pago['monto'] ?? 0), 1);
                    $cuentaPagoId = (string)($pago['cuenta_id'] ?? 0);
                    if (isset($saldosEsperados[$cuentaPagoId])) {
                        $saldosEsperados[$cuentaPagoId]['ventas'] += (float)($pago['monto'] ?? 0);
                    }
                }
            } else {
                $cuentaEfectivoId = $cuentaRepo->buscarPorNombre('Caja Registradora')?->getId();
                $cuentaNequiId = $cuentaRepo->buscarPorNombre('Cuenta Nequi')?->getId();
                $sumarMovimientoEsperado($cuentaEfectivoId, $factura->getPagoEfectivo(), 1);
                $sumarMovimientoEsperado($cuentaNequiId, $factura->getPagoNequi(), 1);
                if ($cuentaEfectivoId && isset($saldosEsperados[(string)$cuentaEfectivoId])) {
                    $saldosEsperados[(string)$cuentaEfectivoId]['ventas'] += $factura->getPagoEfectivo();
                }
                if ($cuentaNequiId && isset($saldosEsperados[(string)$cuentaNequiId])) {
                    $saldosEsperados[(string)$cuentaNequiId]['ventas'] += $factura->getPagoNequi();
                }
            }
        }
        foreach ($abonos as $abono) {
            $esIngreso = $abono->getPrestamo()->getTipo() === 'OTORGADO'
                || ($abono->getPrestamo()->getTipo() === 'RECIBIDO' && $abono->esDesembolso());
            $sumarMovimientoEsperado($abono->getCuentaFinanciera()->getId(), $abono->getMonto(), $esIngreso ? 1 : -1);
            $cuentaAbonoId = (string)$abono->getCuentaFinanciera()->getId();
            if (isset($saldosEsperados[$cuentaAbonoId])) {
                $saldosEsperados[$cuentaAbonoId][$esIngreso ? 'otros_ingresos' : 'egresos'] += $abono->getMonto();
            }
        }
        foreach ($gastos as $gasto) {
            $sumarMovimientoEsperado($gasto->getCuentaFinanciera()->getId(), $gasto->getMonto(), -1);
            $cuentaGastoId = (string)$gasto->getCuentaFinanciera()->getId();
            if (isset($saldosEsperados[$cuentaGastoId])) {
                $saldosEsperados[$cuentaGastoId]['egresos'] += $gasto->getMonto();
            }
        }
        $esperadoEfectivo = 0.0;
        $esperadoNequi = 0.0;
        foreach ($saldosEsperados as $cuenta) {
            if (!$cuenta['incluir_en_arqueo']) {
                continue;
            }
            if ($cuenta['tipo'] === 'CAJA') {
                $esperadoEfectivo += $cuenta['saldo'];
            } else {
                $esperadoNequi += $cuenta['saldo'];
            }
        }
        $contadoEfectivo = (float)($saldosContados['efectivo'] ?? 0);
        $contadoNequi = (float)($saldosContados['nequi'] ?? 0);
        if (isset($saldosContados['cuentas'])) {
            $contadoEfectivo = 0.0;
            $contadoNequi = 0.0;
            foreach ($this->em->getRepository(CuentaFinanciera::class)->findAll() as $cuenta) {
                if (!$cuenta->incluirEnArqueo()) {
                    continue;
                }
                $contado = (float)($saldosContados['cuentas'][(string)$cuenta->getId()] ?? 0);
                if ($cuenta->getTipo() === 'CAJA') {
                    $contadoEfectivo += $contado;
                } else {
                    $contadoNequi += $contado;
                }
            }
        }
        $descuadreEfectivo = round($contadoEfectivo - $esperadoEfectivo, 2);
        $descuadreNequi = round($contadoNequi - $esperadoNequi, 2);
        $diferenciaNetaCotejada = round($descuadreEfectivo + $descuadreNequi, 2);
        $nombreEmpresa = $empresa?->getNombre() ?: 'DON WOK';

        if ($empresa && !$empresa->isImpresoraActiva()) {
            return new JsonResponse(['message' => 'Impresión automática desactivada; cierre listo para continuar']);
        }

        $ticket = "\x1b@\x1ba\x01\x1bE\x01\x1d!\x11" . $nombreEmpresa . "\n";
        $ticket .= "\x1d!\x00\x1bE\x00CIERRE DE CAJA\n";
        $ticket .= "FECHA: " . (new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota')))->format('Y-m-d H:i') . "\n";
        $ticket .= str_repeat('-', 32) . "\n";
        $ticket .= "FACTURAS: " . count($facturas) . "\n";
        $ticket .= "VENTAS: $" . number_format($totalFacturas, 0, ',', '.') . "\n";
        $ticket .= "EFECTIVO ESPERADO: $" . number_format($esperadoEfectivo, 0, ',', '.') . "\n";
        $ticket .= "EFECTIVO CONTADO: $" . number_format($contadoEfectivo, 0, ',', '.') . "\n";
        $ticket .= "DESCUADRE EFECTIVO: $" . number_format($descuadreEfectivo, 0, ',', '.') . "\n";
        $ticket .= "NEQUI ESPERADO: $" . number_format($esperadoNequi, 0, ',', '.') . "\n";
        $ticket .= "NEQUI CONTADO: $" . number_format($contadoNequi, 0, ',', '.') . "\n";
        $ticket .= "DESCUADRE NEQUI: $" . number_format($descuadreNequi, 0, ',', '.') . "\n";
        $ticket .= "DIFERENCIA NETA COTEJADA: $" . number_format($diferenciaNetaCotejada, 0, ',', '.') . "\n";
        $ticket .= "\x1bM\x01DETALLE DE SALDOS ESPERADOS\n";
        foreach ($saldosEsperados as $cuenta) {
            if (!$cuenta['incluir_en_arqueo']) {
                continue;
            }
            $esperadoCuenta = $cuenta['saldo_retenido'] + $cuenta['ventas'] + $cuenta['otros_ingresos'] - $cuenta['egresos'];
            $ticket .= $cuenta['nombre'] . ': retenido $' . number_format($cuenta['saldo_retenido'], 0, ',', '.')
                . ' + ventas $' . number_format($cuenta['ventas'], 0, ',', '.')
                . ' + ingresos $' . number_format($cuenta['otros_ingresos'], 0, ',', '.')
                . ' - egresos $' . number_format($cuenta['egresos'], 0, ',', '.')
                . ' = esperado $' . number_format($esperadoCuenta, 0, ',', '.') . "\n";
        }
        $ticket .= "\x1bM\x00";
        $ticket .= "OTROS INGRESOS: $" . number_format($totalAbonos, 0, ',', '.') . "\n";
        $ticket .= "TOTAL INGRESOS: $" . number_format($totalIngresos, 0, ',', '.') . "\n";
        $ticket .= str_repeat('-', 32) . "\n";
        $ticket .= "DEDUCCIONES:\n";
        foreach ($detalleDeducciones as $deduccion) {
            $ticket .= $deduccion['nombre'] . ': $' . number_format($deduccion['monto'], 0, ',', '.') . "\n";
        }
        $ticket .= "TOTAL DEDUCCIONES: $" . number_format($totalDeducciones, 0, ',', '.') . "\n";
        $ticket .= str_repeat('-', 32) . "\n";
        $ticket .= "EGRESOS: $" . number_format($totalEgresos, 0, ',', '.') . "\n";
        $ticket .= "BALANCE: $" . number_format($totalIngresos - $totalEgresos, 0, ',', '.') . "\n";
        $ticket .= str_repeat('-', 32) . "\n";

        foreach ($facturas as $factura) {
            $ticket .= "\x1bM\x01"; // Fuente B: texto pequeño para el detalle de facturas.
            $ticket .= "FACTURA #" . ($factura->getNumeroFactura() ?: $factura->getId()) . "\n";
            $ticket .= "TOTAL: $" . number_format($factura->getTotal(), 0, ',', '.') . ($factura->fueEditada() ? " [EDITADA]\n" : "\n");
            foreach ($factura->getDetalles() as $detalle) {
                if (!$detalle->estaActivo()) {
                    continue;
                }
                $nombre = mb_strimwidth($detalle->getNombreProducto(), 0, 20, '');
                $subtotal = $detalle->getPrecioUnitario() * $detalle->getCantidad();
                $ticket .= "+ " . $detalle->getCantidad() . "x " . $nombre
                    . " $" . number_format($detalle->getPrecioUnitario(), 0, ',', '.')
                    . " = $" . number_format($subtotal, 0, ',', '.') . "\n";
            }
            foreach ($factura->getDetalles() as $detalle) {
                if ($detalle->estaActivo()) {
                    continue;
                }
                $nombre = mb_strimwidth($detalle->getNombreProducto(), 0, 20, '');
                $ticket .= "- ELIMINADO: " . $detalle->getCantidad() . "x " . $nombre
                    . " $" . number_format($detalle->getPrecioUnitario(), 0, ',', '.') . "\n";
            }
            $ticket .= "\x1bM\x00";
            $ticket .= str_repeat('-', 32) . "\n";
        }

        $ticket .= "\nCierre realizado correctamente\n";
        $ticket .= "Gracias por preferirnos\n" . "\x1bd\x06\x1d\x56\x00";

        try {
            $process = new Process(['lp', '-o', 'raw', '-d', $this->printerName], null, null, $ticket);
            $process->setTimeout(15);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'CUPS no pudo imprimir el cierre.');
            }

            return new JsonResponse(['message' => 'Cierre enviado a la impresora ' . $this->printerName]);
        } catch (\Throwable $e) {
            error_log('Error imprimiendo cierre: ' . $e->getMessage());
            return new JsonResponse(['message' => 'No se pudo imprimir el cierre: ' . $e->getMessage()], 500);
        }
    }

    private function obtenerSaldosContados(Request $request): array
    {
        $data = json_decode($request->getContent(), true) ?? [];
        if (isset($data['cuentas']) && is_array($data['cuentas'])) {
            $cuentas = [];
            foreach ($data['cuentas'] as $cuentaId => $valor) {
                if (!is_numeric($cuentaId) || !is_numeric($valor) || (float)$valor < 0) {
                    throw new \InvalidArgumentException('Todos los saldos contados deben ser números válidos y no negativos.');
                }
                $cuentas[(string)(int)$cuentaId] = (float)$valor;
            }
            if ($cuentas === []) {
                throw new \InvalidArgumentException('Debe ingresar el saldo contado de cada cuenta.');
            }

            return ['cuentas' => $cuentas];
        }
        if (!array_key_exists('efectivo', $data) || !array_key_exists('nequi', $data)) {
            throw new \InvalidArgumentException('Debe ingresar el efectivo y el saldo de Nequi contados.');
        }

        $efectivo = (float)$data['efectivo'];
        $nequi = (float)$data['nequi'];
        if ($efectivo < 0 || $nequi < 0) {
            throw new \InvalidArgumentException('Los saldos contados no pueden ser negativos.');
        }

        return ['efectivo' => $efectivo, 'nequi' => $nequi];
    }

    #[Route('/movimientos', name: 'app_caja_movimientos', methods: ['GET'])]
    public function movimientos(Request $request, MovimientoFinancieroRepositoryInterface $repo): JsonResponse
    {
        $pagina = (int)$request->query->get('pagina', 1);
        $limite = (int)$request->query->get('limite', 20);
        $desdeStr = $request->query->get('desde');
        $hastaStr = $request->query->get('hasta');

        $desde = $desdeStr ? new \DateTime($desdeStr) : null;
        $hasta = $hastaStr ? new \DateTime($hastaStr) : null;

        $resultado = $repo->buscarPaginados($pagina, $limite, $desde, $hasta);

        return new JsonResponse([
            'items' => array_map(fn($m) => [
                'id' => $m->getId(),
                'tipo' => $m->getTipoMovimiento(),
                'monto' => $m->getMonto(),
                'fecha' => $m->getFechaMovimiento()->format('Y-m-d H:i:s'),
                'fecha_actualizacion' => $m->getFechaActualizacion()->format('Y-m-d H:i:s'),
                'descripcion' => $m->getDescripcion(),
                'categoria' => $m->getCategoriaFinanciera()->getNombre(),
                'cuenta' => $m->getCuentaFinanciera()->getNombre()
            ], $resultado['items']),
            'total' => $resultado['total'],
            'pagina' => $pagina,
            'limite' => $limite
        ]);
    }

    #[Route('/movimientos/imprimir', name: 'app_caja_movimientos_imprimir', methods: ['POST'])]
    public function imprimirMovimientosSeleccionados(Request $request, MovimientoFinancieroRepositoryInterface $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $data['ids'] ?? []), fn(int $id) => $id > 0)));
        if ($ids === []) {
            return new JsonResponse(['message' => 'Selecciona al menos un movimiento para imprimir.'], 400);
        }

        $movimientos = $repo->findBy(['id' => $ids], ['fechaActualizacion' => 'ASC', 'id' => 'ASC']);
        if ($movimientos === []) {
            return new JsonResponse(['message' => 'No se encontraron los movimientos seleccionados.'], 404);
        }

        $ticket = "\x1b@\x1ba\x01\x1bE\x01DON WOK\n";
        $ticket .= "\x1bE\x00MOVIMIENTOS SELECCIONADOS\n";
        $ticket .= "FECHA: " . (new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota')))->format('Y-m-d H:i:s') . "\n";
        $ticket .= str_repeat('-', 32) . "\n";
        $total = 0.0;
        foreach ($movimientos as $movimiento) {
            $total += (float)$movimiento->getMonto();
            $ticket .= '#' . $movimiento->getId() . ' ' . $movimiento->getTipoMovimiento() . "\n";
            $ticket .= $movimiento->getDescripcion() ?: 'Sin descripción';
            $ticket .= "\nCUENTA: " . $movimiento->getCuentaFinanciera()->getNombre() . "\n";
            $ticket .= 'MONTO: COP ' . number_format((float)$movimiento->getMonto(), 0, ',', '.') . "\n";
            $ticket .= str_repeat('-', 32) . "\n";
        }
        $ticket .= 'TOTAL MOVIMIENTOS: COP ' . number_format($total, 0, ',', '.') . "\n";
        $ticket .= "\x1bd\x06\x1d\x56\x00";

        $process = new Process(['lp', '-o', 'raw', '-d', $this->printerName], null, null, $ticket);
        $process->setTimeout(15);
        $process->run();
        if (!$process->isSuccessful()) {
            return new JsonResponse(['message' => trim($process->getErrorOutput()) ?: 'No se pudo imprimir la selección.'], 500);
        }

        return new JsonResponse(['message' => 'Movimientos enviados a impresión.', 'cantidad' => count($movimientos)]);
    }
}
