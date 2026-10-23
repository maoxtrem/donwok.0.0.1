<?php

namespace App\UI\Http\Web\Controller;

use App\Domain\Entity\PuestoTrabajo;
use App\Domain\Entity\TurnoPuesto;
use App\Infrastructure\Doctrine\Repository\PuestoTrabajoRepository;
use App\Infrastructure\Doctrine\Repository\TurnoPuestoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/puestos-trabajo')]
class PuestoTrabajoController extends AbstractController
{
    public function __construct(
        private PuestoTrabajoRepository $repo,
        private TurnoPuestoRepository $turnoRepo,
        private EntityManagerInterface $em,
        private string $printerName
    ) {}

    #[Route('', methods: ['GET'])]
    public function listar(): JsonResponse
    {
        return new JsonResponse(array_map(function (PuestoTrabajo $puesto): array {
            $turno = $this->turnoRepo->turnoActivo($puesto);
            return array_merge($puesto->toArray(), [
                'turnoActivo' => $turno ? [
                    'id' => $turno->getId(),
                    'inicio' => $turno->getInicio()->format('Y-m-d H:i:s'),
                    'valorHora' => $turno->getValorHora(),
                ] : null,
            ]);
        }, $this->repo->listarTodos()));
    }

    #[Route('/publicos', methods: ['GET'])]
    public function listarPublicosDelDia(): JsonResponse
    {
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $diaActual = $dias[(int)(new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota')))->format('w')];

        return new JsonResponse(array_map(function (PuestoTrabajo $puesto): array {
            $turno = $this->turnoRepo->turnoActivo($puesto);
            return array_merge($puesto->toArray(), [
                'turnoActivo' => $turno ? [
                    'id' => $turno->getId(),
                    'inicio' => $turno->getInicio()->format('Y-m-d H:i:s'),
                    'valorHora' => $turno->getValorHora(),
                ] : null,
            ]);
        }, $this->repo->listarPublicosParaDia($diaActual)));
    }

    #[Route('', methods: ['POST'])]
    public function crear(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new BadRequestHttpException('El cuerpo de la solicitud no es válido.');
        }

        $titulo = trim((string)($data['titulo'] ?? ''));
        if ($titulo === '') {
            throw new BadRequestHttpException('El título del puesto es obligatorio.');
        }

        $puesto = new PuestoTrabajo(
            $titulo,
            (string)($data['codigoPuesto'] ?? ''),
            (string)($data['clavePuesto'] ?? ''),
            $data['descripcion'] ?? null,
            $data['ubicacion'] ?? null,
            $data['tipoContrato'] ?? null,
            array_key_exists('salario', $data) && $data['salario'] !== '' ? (float)$data['salario'] : null,
            array_key_exists('valorHora', $data) && $data['valorHora'] !== '' ? (float)$data['valorHora'] : null,
            (int)($data['denominacionMinima'] ?? 50),
            $data['horaInicio'] ?? null,
            $data['horaCierre'] ?? null,
            is_array($data['diasActivos'] ?? null) ? $data['diasActivos'] : []
        );
        $this->em->persist($puesto);
        $this->em->flush();

        return new JsonResponse($puesto->toArray(), JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function editar(int $id, Request $request): JsonResponse
    {
        $puesto = $this->repo->find($id);
        if (!$puesto) {
            throw new BadRequestHttpException('El puesto de trabajo no existe.');
        }
        $data = json_decode($request->getContent(), true) ?? [];
        $puesto->actualizar(
            (string)($data['titulo'] ?? ''),
            (string)($data['codigoPuesto'] ?? ''),
            array_key_exists('clavePuesto', $data) ? (string)$data['clavePuesto'] : null,
            $data['descripcion'] ?? null,
            array_key_exists('valorHora', $data) && $data['valorHora'] !== '' ? (float)$data['valorHora'] : null,
            (int)($data['denominacionMinima'] ?? 50),
            is_array($data['diasActivos'] ?? null) ? $data['diasActivos'] : []
        );
        $this->em->flush();
        return new JsonResponse($puesto->toArray());
    }

    #[Route('/{id}/estado', methods: ['PATCH'])]
    public function cambiarEstado(int $id, Request $request): JsonResponse
    {
        $puesto = $this->repo->find($id);
        if (!$puesto) {
            throw new BadRequestHttpException('El puesto de trabajo no existe.');
        }
        $data = json_decode($request->getContent(), true) ?? [];
        $puesto->cambiarEstado((bool)($data['activo'] ?? !$puesto->estaActivo()));
        $this->em->flush();
        return new JsonResponse(['message' => $puesto->estaActivo() ? 'Puesto activado.' : 'Puesto desactivado.', 'activo' => $puesto->estaActivo()]);
    }

    #[Route('/{id}/turno', methods: ['POST'])]
    public function marcarTurno(int $id, Request $request): JsonResponse
    {
        $puesto = $this->repo->find($id);
        if (!$puesto) {
            throw new BadRequestHttpException('El puesto de trabajo no existe.');
        }
        $turno = $this->turnoRepo->turnoActivo($puesto);
        if (!$turno) {
            $data = json_decode($request->getContent(), true) ?? [];
            if (!$puesto->claveValida((string)($data['clave'] ?? ''))) {
                throw new BadRequestHttpException('La clave del puesto no es válida.');
            }
            if ($puesto->getValorHora() === null || $puesto->getValorHora() <= 0) {
                throw new BadRequestHttpException('Este puesto no tiene configurado un valor por hora.');
            }
            $turno = new TurnoPuesto($puesto, $puesto->getValorHora(), $puesto->getDenominacionMinima());
            $this->em->persist($turno);
            $this->em->flush();
            $impresion = $this->enviarTurnoAImpresion($puesto, $turno, false);
            return new JsonResponse([
                'message' => $impresion ? 'Turno iniciado y comprobante enviado a impresión.' : 'Turno iniciado; no fue posible imprimir el comprobante.',
                'estado' => 'ACTIVO',
                'inicio' => $turno->getInicio()->format('Y-m-d H:i:s'),
                'valorHora' => $turno->getValorHora(),
                'codigoCierre' => $turno->getCodigoCierre(),
            ], JsonResponse::HTTP_CREATED);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (!$turno->codigoValido((string)($data['codigo'] ?? ''))) {
            throw new BadRequestHttpException('El código de cierre no es válido.');
        }
        $turno->cerrar();
        $this->em->flush();
        $impresion = $this->enviarTurnoAImpresion($puesto, $turno, true);
        return new JsonResponse([
            'message' => $impresion ? 'Turno cerrado y comprobante enviado a impresión.' : 'Turno cerrado; no fue posible imprimir el comprobante.',
            'estado' => 'CERRADO',
            'inicio' => $turno->getInicio()->format('Y-m-d H:i:s'),
            'cierre' => $turno->getCierre()?->format('Y-m-d H:i:s'),
            'valorHora' => $turno->getValorHora(),
            'denominacionMinima' => $turno->getDenominacionMinima(),
            'minutosLaborados' => $turno->getMinutosLaborados(),
            'montoPagar' => $turno->getMontoPagar(),
        ]);
    }

    #[Route('/{id}/turno/cerrar-administracion', methods: ['POST'])]
    public function cerrarTurnoDesdeAdministracion(int $id): JsonResponse
    {
        $puesto = $this->repo->find($id);
        $turno = $puesto ? $this->turnoRepo->turnoActivo($puesto) : null;
        if (!$puesto || !$turno) {
            throw new BadRequestHttpException('No hay un turno activo para este puesto.');
        }

        $turno->cerrar();
        $this->em->flush();
        $impresion = $this->enviarTurnoAImpresion($puesto, $turno, true);

        return new JsonResponse([
            'message' => $impresion ? 'Turno cerrado por administración y comprobante enviado a impresión.' : 'Turno cerrado por administración; no fue posible imprimir el comprobante.',
            'estado' => 'CERRADO',
            'montoPagar' => $turno->getMontoPagar(),
            'minutosLaborados' => $turno->getMinutosLaborados(),
        ]);
    }

    private function enviarTurnoAImpresion(PuestoTrabajo $puesto, TurnoPuesto $turno, bool $cerrado): bool
    {
        $ticket = "\x1b@\x1ba\x01\x1bE\x01DON WOK\n";
        $ticket .= "\x1bE\x00" . ($cerrado ? "CIERRE DE TURNO\n" : "INICIO DE TURNO\n");
        $ticket .= str_repeat('-', 32) . "\n";
        $ticket .= "\x1d!\x11" . $puesto->getTitulo() . "\n\x1d!\x00";
        $ticket .= 'CODIGO PUESTO: ' . $puesto->getCodigoPuesto() . "\n";
        $ticket .= 'INICIO: ' . $turno->getInicio()->format('Y-m-d H:i:s') . "\n";
        $ticket .= 'VALOR HORA: COP ' . number_format($turno->getValorHora(), 0, ',', '.') . "\n";
        if ($cerrado) {
            $ticket .= 'CIERRE: ' . $turno->getCierre()->format('Y-m-d H:i:s') . "\n";
            $minutos = $turno->getMinutosLaborados();
            $ticket .= 'MINUTOS: ' . $minutos . "\n";
            $ticket .= 'TOTAL A PAGAR: COP ' . number_format($turno->getMontoPagar(), 2, ',', '.') . "\n";
        } else {
            $ticket .= "\x1bE\x01\x1d!\x11CODIGO: " . $turno->getCodigoCierre() . "\n\x1d!\x00\x1bE\x00";
            $ticket .= "ESTADO: TURNO ACTIVO\n";
        }
        $ticket .= str_repeat('-', 32) . "\n\x1bd\x06\x1d\x56\x00";
        $process = new \Symfony\Component\Process\Process(['lp', '-o', 'raw', '-d', $this->printerName], null, null, $ticket);
        $process->setTimeout(15);
        $process->run();
        if (!$process->isSuccessful()) {
            error_log('Error imprimiendo turno: ' . (trim($process->getErrorOutput()) ?: 'impresora no disponible'));
            return false;
        }
        return true;
    }
}
