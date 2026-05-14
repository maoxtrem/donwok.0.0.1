<?php

namespace App\UI\Http\Web\Controller;

use App\Domain\Entity\Inventario;
use App\Domain\Entity\InventarioMovimiento;
use App\Domain\Repository\InventarioRepositoryInterface;
use App\Infrastructure\Doctrine\Repository\InventarioMovimientoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/inventarios')]
class InventarioController extends AbstractController
{
    public function __construct(
        private InventarioRepositoryInterface $repo,
        private InventarioMovimientoRepository $movRepo
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->repo->buscarTodos();
        $total = $this->repo->totalDineroInventario();

        return new JsonResponse([
            'registros' => array_map(fn (Inventario $i) => $i->toArray(), $items),
            'totalInventario' => $total,
        ]);
    }

    #[Route('/historial', methods: ['GET'])]
    public function historial(): JsonResponse
    {
        $items = $this->movRepo->listarRecientes(300);
        return new JsonResponse(array_map(fn (InventarioMovimiento $m) => $m->toArray(), $items));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $nombre = trim((string) ($data['nombre'] ?? ''));
        $unidadMedida = (string) ($data['unidadMedida'] ?? '');
        $cantidad = (float) ($data['cantidad'] ?? 0);
        $costo = (float) ($data['costo'] ?? 0);

        if ($nombre === '') {
            throw new BadRequestHttpException('El nombre es obligatorio.');
        }

        if ($this->repo->buscarPorNombre($nombre)) {
            throw new BadRequestHttpException('Ya existe un registro con ese nombre en inventario.');
        }

        $inventario = new Inventario($nombre, $unidadMedida, $cantidad, $costo);
        $this->repo->guardar($inventario);

        $usuario = $this->getUser()?->getUserIdentifier() ?? 'sistema';
        $mov = new InventarioMovimiento(
            $inventario,
            'CREACION',
            0,
            $inventario->getCantidad(),
            $inventario->getCantidad(),
            0,
            $inventario->getCostoPromedio(),
            $usuario
        );
        $this->movRepo->getEntityManager()->persist($mov);
        $this->movRepo->getEntityManager()->flush();

        return new JsonResponse($inventario->toArray(), JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}/actualizar-cantidad', methods: ['PUT'])]
    public function actualizarCantidad(int $id, Request $request): JsonResponse
    {
        $inventario = $this->repo->buscarPorId($id);
        if (!$inventario) {
            throw new NotFoundHttpException('Registro de inventario no encontrado.');
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $cantidadInput = (float) ($data['cantidad'] ?? 0);

        $costoNuevo = null;
        if (array_key_exists('costo', $data) && $data['costo'] !== '' && $data['costo'] !== null) {
            $costoNuevo = (float) $data['costo'];
        }

        $cantidadAnterior = $inventario->getCantidad();
        $costoAnterior = $inventario->getCostoPromedio();

        if ($costoNuevo === null) {
            // Ahora "cantidad" significa la cantidad FINAL que queda.
            if ($cantidadInput < 0) {
                throw new BadRequestHttpException('La cantidad final no puede ser negativa.');
            }
            if ($cantidadInput > $cantidadAnterior) {
                throw new BadRequestHttpException('La cantidad final no puede ser mayor que la actual sin registrar costo.');
            }

            $cantidadGastada = $cantidadAnterior - $cantidadInput;
            if ($cantidadGastada > 0) {
                $inventario->actualizarCantidad($cantidadGastada, null);
            }
        } else {
            // Con costo, "cantidad" significa cantidad NUEVA que ingresa.
            $inventario->actualizarCantidad($cantidadInput, $costoNuevo);
        }

        $this->repo->guardar($inventario);

        $usuario = $this->getUser()?->getUserIdentifier() ?? 'sistema';
        $mov = new InventarioMovimiento(
            $inventario,
            $costoNuevo === null ? 'AJUSTE_SALIDA' : 'INGRESO_CON_COSTO',
            $cantidadAnterior,
            $inventario->getCantidad(),
            $inventario->getCantidad() - $cantidadAnterior,
            $costoAnterior,
            $inventario->getCostoPromedio(),
            $usuario
        );
        $this->movRepo->getEntityManager()->persist($mov);
        $this->movRepo->getEntityManager()->flush();

        return new JsonResponse($inventario->toArray());
    }
}
